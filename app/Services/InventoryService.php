<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function stockFor(Warehouse $warehouse, Product $product): WarehouseStock
    {
        return WarehouseStock::query()->firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            ['qty_on_hand' => 0, 'qty_reserved' => 0],
        );
    }

    public function receive(Warehouse $warehouse, Product $product, int $qty, ?User $actor = null, ?string $notes = null, ?string $reference = null): WarehouseStock
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Receive quantity must be positive.']);
        }

        return DB::transaction(function () use ($warehouse, $product, $qty, $actor, $notes, $reference) {
            $stock = WarehouseStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = WarehouseStock::query()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                ]);
                $stock = WarehouseStock::query()->whereKey($stock->id)->lockForUpdate()->first();
            }

            $stock->increment('qty_on_hand', $qty);
            $stock->refresh();

            $this->writeLedger($warehouse, $product, InventoryLedger::TYPE_RECEIVE, $qty, $stock, null, $reference, $notes, $actor);
            $this->syncProductTotals($product);

            $this->auditLogger->log('inventory', 'receive', "Received {$qty} of {$product->sku} into {$warehouse->code}", $product, null, [
                'warehouse_id' => $warehouse->id, 'qty' => $qty,
            ], $actor);

            return $stock;
        });
    }

    public function adjust(Warehouse $warehouse, Product $product, int $newOnHand, ?User $actor = null, ?string $notes = null): WarehouseStock
    {
        return DB::transaction(function () use ($warehouse, $product, $newOnHand, $actor, $notes) {
            $stock = $this->lockedStock($warehouse, $product);

            if ($newOnHand < $stock->qty_reserved) {
                throw ValidationException::withMessages([
                    'qty' => 'On-hand cannot be below reserved quantity ('.$stock->qty_reserved.').',
                ]);
            }

            $delta = $newOnHand - (int) $stock->qty_on_hand;
            $stock->update(['qty_on_hand' => $newOnHand]);
            $stock->refresh();

            $this->writeLedger($warehouse, $product, InventoryLedger::TYPE_ADJUST, $delta, $stock, null, null, $notes, $actor);
            $this->syncProductTotals($product);

            return $stock;
        });
    }

    public function transfer(Warehouse $from, Warehouse $to, Product $product, int $qty, ?User $actor = null, ?string $notes = null): void
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['warehouse' => 'Choose two different warehouses.']);
        }
        if ($qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Transfer quantity must be positive.']);
        }

        DB::transaction(function () use ($from, $to, $product, $qty, $actor, $notes) {
            $source = $this->lockedStock($from, $product);
            if ($source->available() < $qty) {
                throw ValidationException::withMessages([
                    'qty' => "Only {$source->available()} available at {$from->code}.",
                ]);
            }

            $source->decrement('qty_on_hand', $qty);
            $source->refresh();
            $this->writeLedger($from, $product, InventoryLedger::TYPE_TRANSFER_OUT, -$qty, $source, null, $to->code, $notes, $actor);

            $dest = $this->lockedStock($to, $product);
            $dest->increment('qty_on_hand', $qty);
            $dest->refresh();
            $this->writeLedger($to, $product, InventoryLedger::TYPE_TRANSFER_IN, $qty, $dest, null, $from->code, $notes, $actor);

            $this->syncProductTotals($product);
        });
    }

    public function reserve(Warehouse $warehouse, Product $product, int $qty, Order $order, ?User $actor = null): WarehouseStock
    {
        return DB::transaction(function () use ($warehouse, $product, $qty, $order, $actor) {
            $stock = $this->lockedStock($warehouse, $product);

            if ($stock->available() < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "{$product->sku}: need {$qty}, available {$stock->available()} at {$warehouse->code}",
                ]);
            }

            $stock->increment('qty_reserved', $qty);
            $stock->refresh();

            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $product->increment('reserved_stock', $qty);

            $this->writeLedger($warehouse, $product, InventoryLedger::TYPE_RESERVE, $qty, $stock, $order, $order->number, 'Order reservation', $actor);

            return $stock;
        });
    }

    public function release(Warehouse $warehouse, Product $product, int $qty, Order $order, ?User $actor = null): WarehouseStock
    {
        return DB::transaction(function () use ($warehouse, $product, $qty, $order, $actor) {
            $stock = $this->lockedStock($warehouse, $product);
            $release = min($qty, (int) $stock->qty_reserved);
            $stock->update(['qty_reserved' => max(0, (int) $stock->qty_reserved - $release)]);
            $stock->refresh();

            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $product->update(['reserved_stock' => max(0, (int) $product->reserved_stock - $release)]);

            $this->writeLedger($warehouse, $product, InventoryLedger::TYPE_RELEASE, -$release, $stock, $order, $order->number, 'Reservation released', $actor);

            return $stock;
        });
    }

    /** Commit pick: reduce on-hand and reserved together. */
    public function pick(Warehouse $warehouse, Product $product, int $qty, Order $order, ?User $actor = null): WarehouseStock
    {
        return DB::transaction(function () use ($warehouse, $product, $qty, $order, $actor) {
            $stock = $this->lockedStock($warehouse, $product);

            if ((int) $stock->qty_on_hand < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "{$product->sku}: on-hand {$stock->qty_on_hand} < pick {$qty}",
                ]);
            }

            $stock->update([
                'qty_on_hand' => (int) $stock->qty_on_hand - $qty,
                'qty_reserved' => max(0, (int) $stock->qty_reserved - $qty),
            ]);
            $stock->refresh();

            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $product->update([
                'stock_on_hand' => max(0, (int) $product->stock_on_hand - $qty),
                'reserved_stock' => max(0, (int) $product->reserved_stock - $qty),
            ]);

            $this->writeLedger($warehouse, $product, InventoryLedger::TYPE_PICK, -$qty, $stock, $order, $order->number, 'Picked for fulfilment', $actor);

            return $stock;
        });
    }

    public function syncProductTotals(Product $product): void
    {
        $totals = WarehouseStock::query()
            ->where('product_id', $product->id)
            ->selectRaw('COALESCE(SUM(qty_on_hand),0) as on_hand, COALESCE(SUM(qty_reserved),0) as reserved')
            ->first();

        $product->forceFill([
            'stock_on_hand' => (int) ($totals->on_hand ?? 0),
            'reserved_stock' => (int) ($totals->reserved ?? 0),
        ])->save();
    }

    public function seedProductIntoWarehouse(Warehouse $warehouse, Product $product): WarehouseStock
    {
        $stock = $this->stockFor($warehouse, $product);
        if ((int) $stock->qty_on_hand === 0 && (int) $product->stock_on_hand > 0) {
            $stock->update([
                'qty_on_hand' => (int) $product->stock_on_hand,
                'qty_reserved' => (int) $product->reserved_stock,
            ]);
        }

        return $stock->fresh();
    }

    private function lockedStock(Warehouse $warehouse, Product $product): WarehouseStock
    {
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            WarehouseStock::query()->create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'qty_on_hand' => 0,
                'qty_reserved' => 0,
            ]);
            $stock = WarehouseStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return $stock;
    }

    private function writeLedger(
        Warehouse $warehouse,
        Product $product,
        string $type,
        int $delta,
        WarehouseStock $stock,
        ?Order $order,
        ?string $reference,
        ?string $notes,
        ?User $actor,
    ): void {
        InventoryLedger::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'order_id' => $order?->id,
            'type' => $type,
            'qty_delta' => $delta,
            'qty_on_hand_after' => $stock->qty_on_hand,
            'qty_reserved_after' => $stock->qty_reserved,
            'reference' => $reference,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
    }
}
