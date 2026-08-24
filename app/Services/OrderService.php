<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private InventoryService $inventory,
        private FulfilmentService $fulfilment,
    ) {}

    /**
     * @param  list<array{product_id:int, quantity:int}>  $lines
     */
    public function collectFromSalesman(
        User $salesman,
        Shop $shop,
        array $lines,
        ?ShopVisit $visit = null,
        ?string $notes = null,
    ): Order {
        if ($shop->assigned_salesman_id && $shop->assigned_salesman_id !== $salesman->id
            && ! $salesman->isSuperAdmin() && ! $salesman->hasGlobalAccessScope()) {
            throw ValidationException::withMessages([
                'shop_id' => 'This shop is not assigned to you.',
            ]);
        }

        return $this->submitOrder(
            shop: $shop,
            lines: $lines,
            actor: $salesman,
            source: Order::SOURCE_SALESMAN,
            salesmanId: $salesman->id,
            visit: $visit,
            notes: $notes,
        );
    }

    /**
     * @param  list<array{product_id:int, quantity:int}>  $lines
     */
    public function placeFromShopPortal(User $shopUser, Shop $shop, array $lines, ?string $notes = null, ?int $salesmanId = null): Order
    {
        if (! $shop->users()->where('users.id', $shopUser->id)->exists()) {
            throw ValidationException::withMessages([
                'shop' => 'You are not linked to this shop.',
            ]);
        }

        return $this->submitOrder(
            shop: $shop,
            lines: $lines,
            actor: $shopUser,
            source: Order::SOURCE_SHOP_PORTAL,
            salesmanId: $salesmanId ?: $shop->assigned_salesman_id,
            notes: $notes,
        );
    }

    /**
     * @param  list<array{product_id:int, quantity:int}>  $lines
     */
    public function createFromAdmin(User $admin, Shop $shop, array $lines, ?string $notes = null, ?int $salesmanId = null): Order
    {
        return $this->submitOrder(
            shop: $shop,
            lines: $lines,
            actor: $admin,
            source: Order::SOURCE_ADMIN,
            salesmanId: $salesmanId ?: $shop->assigned_salesman_id,
            notes: $notes,
        );
    }

    public function approve(Order $order, User $actor, ?string $notes = null, bool $creditOverride = false): Order
    {
        if (! $order->isPendingAudit()) {
            throw ValidationException::withMessages([
                'order' => 'Only orders pending Super Admin audit can be approved.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $notes, $creditOverride) {
            $order = Order::query()->lockForUpdate()->with(['items', 'shop'])->findOrFail($order->id);
            $shop = $order->shop;
            $availableCredit = $shop->availableCredit();
            $warehouse = Warehouse::defaultWarehouse();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse' => 'Configure at least one active warehouse before approving orders.',
                ]);
            }

            if ($shop->status === \App\Models\Shop::STATUS_ON_HOLD && ! $creditOverride) {
                throw ValidationException::withMessages([
                    'credit' => 'Shop is on credit hold. Clear outstanding balance or use credit override.',
                ]);
            }

            if ($order->total > $availableCredit && ! $creditOverride) {
                throw ValidationException::withMessages([
                    'credit' => 'Shop credit is insufficient (available ৳ '.number_format($availableCredit, 2).'). Enable credit override to approve anyway.',
                ]);
            }

            if ($order->total > $availableCredit && $creditOverride && ! $actor->can('orders.approve')) {
                throw ValidationException::withMessages([
                    'credit' => 'You cannot override credit limits.',
                ]);
            }

            $stockIssues = [];

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    $stockIssues[] = "{$item->product_sku}: product missing";

                    continue;
                }

                $product = Product::query()->find($item->product_id);

                if (! $product) {
                    $stockIssues[] = "{$item->product_sku}: product missing";

                    continue;
                }

                $whStock = $this->inventory->stockFor($warehouse, $product);
                $available = $whStock->available();
                $item->update(['available_at_audit' => $available]);

                if ($available < $item->quantity) {
                    $stockIssues[] = "{$item->product_sku}: need {$item->quantity}, available {$available} at {$warehouse->code}";
                }
            }

            if ($stockIssues !== []) {
                throw ValidationException::withMessages([
                    'stock' => 'Insufficient stock: '.implode('; ', $stockIssues),
                ]);
            }

            foreach ($order->items as $item) {
                $product = Product::query()->findOrFail($item->product_id);
                $this->inventory->reserve($warehouse, $product, (int) $item->quantity, $order, $actor);
                $item->update(['reserved_quantity' => $item->quantity]);
            }

            $from = $order->status;

            $order->update([
                'status' => Order::STATUS_APPROVED,
                'warehouse_id' => $warehouse->id,
                'audited_by' => $actor->id,
                'audited_at' => now(),
                'stock_reserved_at' => now(),
                'stock_reserved' => true,
                'audit_notes' => $notes,
                'credit_available_at_audit' => $availableCredit,
                'credit_override' => $creditOverride && $order->total > $availableCredit,
                'rejection_reason' => null,
            ]);

            $this->fulfilment->createForApprovedOrder($order, $warehouse, $actor);

            $this->recordHistory($order, $from, Order::STATUS_APPROVED, 'approved_reserved', $notes, [
                'credit_available' => $availableCredit,
                'credit_override' => $order->credit_override,
                'total' => (float) $order->total,
                'warehouse_id' => $warehouse->id,
            ], $actor);

            $this->auditLogger->log(
                'orders',
                'approved',
                "Order {$order->number} approved and stock reserved at {$warehouse->code}",
                $order,
                ['status' => $from],
                ['status' => Order::STATUS_APPROVED, 'stock_reserved' => true],
                $actor,
            );

            return $order->fresh(['items.product', 'shop', 'salesman', 'auditor', 'fulfilment', 'warehouse', 'statusHistories.user']);
        });
    }

    public function reject(Order $order, User $actor, string $reason): Order
    {
        if (! $order->isPendingAudit()) {
            throw ValidationException::withMessages([
                'order' => 'Only pending audit orders can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $reason) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            $order->update([
                'status' => Order::STATUS_REJECTED,
                'audited_by' => $actor->id,
                'audited_at' => now(),
                'rejection_reason' => $reason,
                'audit_notes' => $reason,
                'credit_available_at_audit' => $order->shop?->availableCredit(),
                'stock_reserved' => false,
            ]);

            $this->recordHistory($order, $from, Order::STATUS_REJECTED, 'rejected', $reason, null, $actor);

            $this->auditLogger->log(
                'orders',
                'rejected',
                "Order {$order->number} rejected",
                $order,
                ['status' => $from],
                ['status' => Order::STATUS_REJECTED, 'reason' => $reason],
                $actor,
            );

            return $order->fresh(['items', 'shop', 'salesman', 'auditor', 'statusHistories.user']);
        });
    }

    public function cancel(Order $order, User $actor, string $reason): Order
    {
        if (! in_array($order->status, [Order::STATUS_PENDING_AUDIT, Order::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages([
                'order' => 'Only pending or approved orders can be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $reason) {
            $order = Order::query()->lockForUpdate()->with(['items.product', 'warehouse', 'fulfilment'])->findOrFail($order->id);
            $from = $order->status;

            if ($order->fulfilment && ! in_array($order->fulfilment->status, [
                \App\Models\OrderFulfilment::STATUS_AWAITING_PICK,
            ], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Cannot cancel after warehouse picking has started.',
                ]);
            }

            if ($order->stock_reserved) {
                $warehouse = $order->warehouse ?: Warehouse::defaultWarehouse();
                foreach ($order->items as $item) {
                    $qty = (int) $item->reserved_quantity;
                    if ($qty > 0 && $item->product_id && $warehouse) {
                        $this->inventory->release($warehouse, $item->product, $qty, $order, $actor);
                        $item->update(['reserved_quantity' => 0]);
                    }
                }
            }

            if ($order->fulfilment) {
                $order->fulfilment->delete();
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'stock_reserved' => false,
                'stock_reserved_at' => null,
            ]);

            $this->recordHistory($order, $from, Order::STATUS_CANCELLED, 'cancelled', $reason, [
                'released_reservation' => $from === Order::STATUS_APPROVED,
            ], $actor);

            $this->auditLogger->log(
                'orders',
                'cancelled',
                "Order {$order->number} cancelled",
                $order,
                ['status' => $from],
                ['status' => Order::STATUS_CANCELLED, 'reason' => $reason],
                $actor,
            );

            return $order->fresh(['items.product', 'shop', 'salesman', 'statusHistories.user']);
        });
    }

    /**
     * Snapshot stock + credit readiness for the audit screen (no locks).
     *
     * @return array{credit_ok:bool, credit_available:float, stock_ok:bool, lines:list<array>}
     */
    public function auditSnapshot(Order $order): array
    {
        $order->loadMissing(['items.product', 'shop']);
        $creditAvailable = $order->shop?->availableCredit() ?? 0.0;
        $lines = [];
        $stockOk = true;

        foreach ($order->items as $item) {
            $available = $item->product?->availableStock() ?? 0;
            $ok = $available >= $item->quantity;
            if (! $ok) {
                $stockOk = false;
            }
            $lines[] = [
                'sku' => $item->product_sku,
                'name' => $item->product_name,
                'qty' => $item->quantity,
                'available' => $available,
                'ok' => $ok,
            ];
        }

        return [
            'credit_ok' => $order->total <= $creditAvailable,
            'credit_available' => (float) $creditAvailable,
            'stock_ok' => $stockOk,
            'lines' => $lines,
        ];
    }

    public function nextNumber(): string
    {
        $seq = Order::withTrashed()->count() + 1;

        return 'ORD-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  list<array{product_id:int, quantity:int}>  $lines
     */
    private function submitOrder(
        Shop $shop,
        array $lines,
        User $actor,
        string $source,
        ?int $salesmanId = null,
        ?ShopVisit $visit = null,
        ?string $notes = null,
    ): Order {
        if ($shop->status !== Shop::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'shop_id' => 'Orders can only be placed for active shops.',
            ]);
        }

        $lines = collect($lines)
            ->filter(fn ($line) => (int) ($line['quantity'] ?? 0) > 0)
            ->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product with quantity.',
            ]);
        }

        return DB::transaction(function () use ($shop, $lines, $actor, $source, $salesmanId, $visit, $notes) {
            $shop->loadMissing('priceGroup');

            $order = Order::query()->create([
                'number' => $this->nextNumber(),
                'shop_id' => $shop->id,
                'salesman_id' => $salesmanId,
                'visit_id' => $visit?->id,
                'source' => $source,
                'status' => Order::STATUS_PENDING_AUDIT,
                'notes' => $notes,
                'submitted_at' => now(),
                'created_by' => $actor->id,
            ]);

            $subtotal = 0;
            $itemCount = 0;

            foreach ($lines as $line) {
                $product = Product::query()->findOrFail($line['product_id']);

                if ($product->status !== Product::STATUS_ACTIVE || ! $product->is_published) {
                    throw ValidationException::withMessages([
                        'items' => "Product {$product->sku} is not available.",
                    ]);
                }

                $qty = (int) $line['quantity'];
                $unit = $product->priceForGroup($shop->priceGroup);
                $lineTotal = round($unit * $qty, 2);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $itemCount += $qty;
            }

            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'total' => $subtotal,
                'item_count' => $itemCount,
            ]);

            if ($visit) {
                $visit->update([
                    'order_id' => $order->id,
                    'outcome' => ShopVisit::OUTCOME_ORDER_TAKEN,
                ]);
            }

            $this->recordHistory($order, null, Order::STATUS_PENDING_AUDIT, 'submitted_for_audit', $notes, [
                'source' => $source,
                'total' => $subtotal,
            ], $actor);

            $this->auditLogger->log(
                'orders',
                'submitted_for_audit',
                "Order {$order->number} submitted for Super Admin audit",
                $order,
                null,
                ['shop_id' => $shop->id, 'total' => $subtotal, 'items' => $itemCount, 'source' => $source],
                $actor,
            );

            $fresh = $order->fresh(['items', 'shop', 'salesman', 'visit']);

            DB::afterCommit(function () use ($fresh) {
                try {
                    app(AppNotificationService::class)->orderPendingAudit($fresh);
                } catch (\Throwable) {
                    // non-blocking
                }
            });

            return $fresh;
        });
    }

    private function recordHistory(
        Order $order,
        ?string $from,
        string $to,
        string $event,
        ?string $notes,
        ?array $meta,
        ?User $actor,
    ): void {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'event' => $event,
            'notes' => $notes,
            'meta' => $meta,
            'user_id' => $actor?->id,
        ]);
    }
}
