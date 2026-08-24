<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private InventoryService $inventory,
    ) {}

    /**
     * @param  list<array{product_id:int, quantity:int, unit_cost_bdt?:float|int|string, purchase_item_id?:int|null}>  $lines
     */
    public function create(array $data, array $lines, ?User $actor = null): Shipment
    {
        $lines = collect($lines)->filter(fn ($l) => (int) ($l['quantity'] ?? 0) > 0)->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Add at least one shipment product line.']);
        }

        return DB::transaction(function () use ($data, $lines, $actor) {
            $shipment = Shipment::query()->create([
                'number' => $data['number'] ?? $this->nextNumber(),
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_id' => $data['purchase_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? Warehouse::defaultWarehouse()?->id,
                'origin' => $data['origin'] ?? 'China',
                'carrier' => $data['carrier'] ?? null,
                'tracking_ref' => $data['tracking_ref'] ?? null,
                'container_no' => $data['container_no'] ?? null,
                'status' => $data['status'] ?? Shipment::STATUS_IN_TRANSIT,
                'shipped_at' => $data['shipped_at'] ?? now()->toDateString(),
                'eta_at' => $data['eta_at'] ?? null,
                'freight_cost' => $data['freight_cost'] ?? 0,
                'customs_duty' => $data['customs_duty'] ?? 0,
                'insurance_cost' => $data['insurance_cost'] ?? 0,
                'other_cost' => $data['other_cost'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
            ]);

            $goods = 0;
            foreach ($lines as $line) {
                $qty = (int) $line['quantity'];
                $unit = (float) ($line['unit_cost_bdt'] ?? 0);
                $lineGoods = round($unit * $qty, 2);

                ShipmentItem::query()->create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $line['product_id'],
                    'purchase_item_id' => $line['purchase_item_id'] ?? null,
                    'quantity' => $qty,
                    'unit_cost_bdt' => $unit,
                    'line_goods_value' => $lineGoods,
                ]);

                $goods += $lineGoods;
            }

            $shipment->update(['goods_value_bdt' => round($goods, 2)]);
            $this->recalculateTotals($shipment->fresh('items'));

            $this->auditLogger->log(
                'shipments',
                'created',
                "Created shipment {$shipment->number}",
                $shipment,
                null,
                ['origin' => $shipment->origin, 'goods_value_bdt' => $shipment->goods_value_bdt],
                $actor,
            );

            return $shipment->fresh(['items.product', 'supplier', 'purchase', 'warehouse']);
        });
    }

    public function createFromPurchase(Purchase $purchase, array $data, ?User $actor = null): Shipment
    {
        $purchase->load('items');
        $lines = $purchase->items->map(fn (PurchaseItem $item) => [
            'product_id' => $item->product_id,
            'purchase_item_id' => $item->id,
            'quantity' => $item->remainingQuantity() ?: $item->quantity,
            'unit_cost_bdt' => $item->unit_cost_bdt,
        ])->filter(fn ($l) => $l['quantity'] > 0)->values()->all();

        return $this->create([
            ...$data,
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'status' => $data['status'] ?? Shipment::STATUS_IN_TRANSIT,
        ], $lines, $actor);
    }

    public function markArrived(Shipment $shipment, ?User $actor = null): Shipment
    {
        if ($shipment->status === Shipment::STATUS_RECEIVED) {
            throw ValidationException::withMessages(['shipment' => 'Shipment already received.']);
        }

        $shipment->update([
            'status' => Shipment::STATUS_ARRIVED,
            'arrived_at' => now()->toDateString(),
        ]);

        $this->auditLogger->log('shipments', 'arrived', "Shipment {$shipment->number} arrived", $shipment, null, null, $actor);

        return $shipment;
    }

    public function updateCosts(Shipment $shipment, array $costs, ?User $actor = null): Shipment
    {
        if ($shipment->status === Shipment::STATUS_RECEIVED) {
            throw ValidationException::withMessages(['shipment' => 'Cannot edit costs after receive.']);
        }

        $shipment->update([
            'freight_cost' => $costs['freight_cost'] ?? $shipment->freight_cost,
            'customs_duty' => $costs['customs_duty'] ?? $shipment->customs_duty,
            'insurance_cost' => $costs['insurance_cost'] ?? $shipment->insurance_cost,
            'other_cost' => $costs['other_cost'] ?? $shipment->other_cost,
        ]);

        return $this->recalculateTotals($shipment->fresh('items'));
    }

    public function recalculateTotals(Shipment $shipment): Shipment
    {
        $extra = $shipment->extraCostsTotal();
        $goods = (float) $shipment->items->sum('line_goods_value');
        if ($goods <= 0) {
            $goods = (float) $shipment->goods_value_bdt;
        }

        $allocatedSum = 0;
        $items = $shipment->items;
        $lastIndex = $items->count() - 1;

        foreach ($items as $i => $item) {
            if ($goods > 0) {
                $share = ((float) $item->line_goods_value / $goods) * $extra;
            } else {
                $share = $items->count() > 0 ? $extra / $items->count() : 0;
            }

            // Fix rounding on last line.
            if ($i === $lastIndex) {
                $share = $extra - $allocatedSum;
            } else {
                $share = round($share, 2);
                $allocatedSum += $share;
            }

            $lineLanded = round((float) $item->line_goods_value + $share, 2);
            $unitLanded = $item->quantity > 0 ? round($lineLanded / $item->quantity, 2) : 0;

            $item->update([
                'allocated_extra_cost' => round($share, 2),
                'line_landed_total' => $lineLanded,
                'unit_landed_cost' => $unitLanded,
            ]);
        }

        $shipment->update([
            'goods_value_bdt' => round($goods, 2),
            'total_landed_cost' => round($goods + $extra, 2),
            'costs_allocated' => true,
        ]);

        return $shipment->fresh('items');
    }

    public function receive(Shipment $shipment, ?User $actor = null, ?int $warehouseId = null): Shipment
    {
        if ($shipment->status === Shipment::STATUS_RECEIVED) {
            throw ValidationException::withMessages(['shipment' => 'Shipment already received.']);
        }

        if ($shipment->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Shipment has no product lines.']);
        }

        return DB::transaction(function () use ($shipment, $actor, $warehouseId) {
            $shipment = Shipment::query()->lockForUpdate()->with(['items.product', 'items.purchaseItem', 'purchase'])->findOrFail($shipment->id);
            $warehouse = Warehouse::query()->find($warehouseId ?: $shipment->warehouse_id) ?: Warehouse::defaultWarehouse();

            if (! $warehouse) {
                throw ValidationException::withMessages(['warehouse' => 'No warehouse available to receive into.']);
            }

            $this->recalculateTotals($shipment->fresh('items'));
            $shipment = $shipment->fresh(['items.product', 'items.purchaseItem', 'purchase']);

            foreach ($shipment->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                $this->inventory->receive(
                    $warehouse,
                    $product,
                    (int) $item->quantity,
                    $actor,
                    "Received from shipment {$shipment->number}",
                    $shipment->number,
                );

                // Weighted average landed cost on product (stock already includes incoming qty).
                $product->refresh();
                $newQty = (int) $product->stock_on_hand;
                $incomingQty = (int) $item->quantity;
                $prevQty = max(0, $newQty - $incomingQty);
                $prevLanded = (float) $product->landed_cost;
                $incomingLanded = (float) $item->unit_landed_cost;

                if ($newQty > 0) {
                    $avg = (($prevQty * $prevLanded) + ($incomingQty * $incomingLanded)) / $newQty;
                } else {
                    $avg = $incomingLanded;
                }

                $product->update([
                    'landed_cost' => round($avg, 2),
                    'cost_price' => round($avg * 0.95, 2),
                ]);

                if ($item->purchase_item_id) {
                    $pi = PurchaseItem::query()->lockForUpdate()->find($item->purchase_item_id);
                    if ($pi) {
                        $pi->increment('received_quantity', $item->quantity);
                    }
                }
            }

            if ($shipment->purchase_id) {
                $this->refreshPurchaseStatus($shipment->purchase);
            }

            $shipment->update([
                'warehouse_id' => $warehouse->id,
                'status' => Shipment::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $actor?->id,
                'arrived_at' => $shipment->arrived_at ?: now()->toDateString(),
                'costs_allocated' => true,
            ]);

            $this->auditLogger->log(
                'shipments',
                'received',
                "Shipment {$shipment->number} received into {$warehouse->code}",
                $shipment,
                null,
                [
                    'warehouse_id' => $warehouse->id,
                    'total_landed_cost' => (float) $shipment->total_landed_cost,
                ],
                $actor,
            );

            return $shipment->fresh(['items.product', 'warehouse', 'supplier', 'purchase', 'receiver']);
        });
    }

    public function nextNumber(): string
    {
        $seq = Shipment::withTrashed()->count() + 1;

        return 'SHP-CN-'.now()->format('ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function refreshPurchaseStatus(?Purchase $purchase): void
    {
        if (! $purchase) {
            return;
        }

        $purchase->load('items');
        $allReceived = $purchase->items->every(fn (PurchaseItem $i) => $i->received_quantity >= $i->quantity);
        $anyReceived = $purchase->items->contains(fn (PurchaseItem $i) => $i->received_quantity > 0);

        $purchase->update([
            'status' => $allReceived
                ? Purchase::STATUS_RECEIVED
                : ($anyReceived ? Purchase::STATUS_PARTIAL : $purchase->status),
        ]);
    }
}
