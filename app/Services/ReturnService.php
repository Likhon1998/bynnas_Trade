<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private CreditService $credit,
        private InventoryService $inventory,
    ) {}

    /**
     * @param  list<array{product_id:int, quantity:int, unit_price?:float}>  $lines
     */
    public function create(array $data, array $lines, ?User $actor = null): ProductReturn
    {
        $lines = collect($lines)->filter(fn ($l) => (int) ($l['quantity'] ?? 0) > 0)->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Add at least one return line.']);
        }

        return DB::transaction(function () use ($data, $lines, $actor) {
            $return = ProductReturn::query()->create([
                'number' => $this->nextNumber(),
                'shop_id' => $data['shop_id'],
                'order_id' => $data['order_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'status' => ProductReturn::STATUS_PENDING,
                'reason_type' => $data['reason_type'] ?? ProductReturn::REASON_WARRANTY,
                'reason' => $data['reason'] ?? null,
                'restock' => $data['restock'] ?? true,
                'created_by' => $actor?->id,
            ]);

            $total = 0;
            foreach ($lines as $line) {
                $product = Product::query()->findOrFail($line['product_id']);
                $qty = (int) $line['quantity'];
                $unit = (float) ($line['unit_price'] ?? $product->wholesale_price);
                $lineTotal = round($unit * $qty, 2);

                ReturnItem::query()->create([
                    'return_id' => $return->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $return->update(['total' => round($total, 2)]);

            $this->auditLogger->log(
                'returns',
                'created',
                "Return {$return->number} submitted",
                $return,
                null,
                ['total' => $total, 'reason_type' => $return->reason_type],
                $actor,
            );

            $fresh = $return->fresh(['items.product', 'shop', 'order']);

            DB::afterCommit(function () use ($fresh) {
                try {
                    app(AppNotificationService::class)->returnSubmitted($fresh);
                } catch (\Throwable) {
                    // non-blocking
                }
            });

            return $fresh;
        });
    }

    public function createFromOrder(Order $order, array $quantities, array $meta, ?User $actor = null): ProductReturn
    {
        $order->load('items');
        $lines = [];
        foreach ($order->items as $item) {
            $qty = (int) ($quantities[$item->product_id] ?? $quantities[$item->id] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $lines[] = [
                'product_id' => $item->product_id,
                'quantity' => min($qty, $item->quantity),
                'unit_price' => $item->unit_price,
            ];
        }

        return $this->create([
            'shop_id' => $order->shop_id,
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'reason_type' => $meta['reason_type'] ?? ProductReturn::REASON_WARRANTY,
            'reason' => $meta['reason'] ?? null,
            'restock' => $meta['restock'] ?? true,
        ], $lines, $actor);
    }

    public function approve(ProductReturn $return, ?User $actor = null, ?string $notes = null): ProductReturn
    {
        if ($return->status !== ProductReturn::STATUS_PENDING) {
            throw ValidationException::withMessages(['return' => 'Only pending returns can be approved.']);
        }

        return DB::transaction(function () use ($return, $actor, $notes) {
            $return = ProductReturn::query()->lockForUpdate()->with(['items.product', 'shop'])->findOrFail($return->id);

            if ($return->restock) {
                $warehouse = Warehouse::defaultWarehouse();
                if ($warehouse) {
                    foreach ($return->items as $item) {
                        if (! $item->product_id) {
                            continue;
                        }
                        $this->inventory->receive(
                            $warehouse,
                            $item->product,
                            (int) $item->quantity,
                            $actor,
                            "Return {$return->number} restock",
                            $return->number,
                        );
                    }
                }
            }

            $return->update([
                'status' => ProductReturn::STATUS_APPROVED,
                'credit_issued' => true,
                'approved_at' => now(),
                'approved_by' => $actor?->id,
                'resolution_notes' => $notes,
            ]);

            $this->credit->recalculateOutstanding($return->shop);

            $this->auditLogger->log(
                'returns',
                'approved',
                "Return {$return->number} approved — credit issued",
                $return,
                null,
                ['total' => (float) $return->total, 'restock' => $return->restock],
                $actor,
            );

            return $return->fresh(['items', 'shop', 'approver']);
        });
    }

    public function reject(ProductReturn $return, string $reason, ?User $actor = null): ProductReturn
    {
        if ($return->status !== ProductReturn::STATUS_PENDING) {
            throw ValidationException::withMessages(['return' => 'Only pending returns can be rejected.']);
        }

        $return->update([
            'status' => ProductReturn::STATUS_REJECTED,
            'resolution_notes' => $reason,
            'approved_by' => $actor?->id,
            'approved_at' => now(),
        ]);

        $this->auditLogger->log('returns', 'rejected', "Return {$return->number} rejected", $return, null, ['reason' => $reason], $actor);

        return $return->fresh();
    }

    public function nextNumber(): string
    {
        $seq = ProductReturn::withTrashed()->count() + 1;

        return 'RET-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
