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
        private InvoiceService $invoices,
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
     * Partners may change quantities / notes only while the order awaits Super Admin audit.
     *
     * @param  list<array{product_id:int, quantity:int}>  $lines
     */
    public function updatePendingFromShopPortal(User $shopUser, Shop $shop, Order $order, array $lines, ?string $notes = null): Order
    {
        if (! $shop->users()->where('users.id', $shopUser->id)->exists()) {
            throw ValidationException::withMessages([
                'shop' => 'You are not linked to this shop.',
            ]);
        }

        if ($order->shop_id !== $shop->id) {
            throw ValidationException::withMessages([
                'order' => 'This order does not belong to your shop.',
            ]);
        }

        if (! $order->canPartnerEdit()) {
            throw ValidationException::withMessages([
                'order' => 'Only orders waiting for Super Admin approval can be edited.',
            ]);
        }

        if ($shop->status !== Shop::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'shop' => 'Orders can only be edited while the shop is active.',
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

        return DB::transaction(function () use ($shop, $order, $lines, $shopUser, $notes) {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

            if (! $order->canPartnerEdit() || $order->shop_id !== $shop->id) {
                throw ValidationException::withMessages([
                    'order' => 'Only orders waiting for Super Admin approval can be edited.',
                ]);
            }

            $shop->loadMissing('priceGroup');
            $old = [
                'total' => (float) $order->total,
                'item_count' => (int) $order->item_count,
                'notes' => $order->notes,
            ];

            $order->items()->delete();

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
                'notes' => $notes,
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'total' => $subtotal,
                'item_count' => $itemCount,
                'status' => Order::STATUS_PENDING_AUDIT,
                'submitted_at' => now(),
            ]);

            $this->recordHistory($order, Order::STATUS_PENDING_AUDIT, Order::STATUS_PENDING_AUDIT, 'edited_before_audit', $notes, [
                'old_total' => $old['total'],
                'new_total' => $subtotal,
                'old_item_count' => $old['item_count'],
                'new_item_count' => $itemCount,
            ], $shopUser);

            $this->auditLogger->log(
                'orders',
                'edited_before_audit',
                "Order {$order->number} edited by shop partner before audit",
                $order,
                $old,
                ['total' => $subtotal, 'item_count' => $itemCount, 'notes' => $notes],
                $shopUser,
            );

            return $order->fresh(['items', 'shop', 'salesman']);
        });
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


    public function requestAdvance(Order $order, User $actor, float $amount, ?string $notes = null): Order
    {
        if (! $order->isPendingAudit()) {
            throw ValidationException::withMessages([
                'order' => 'Advance can only be requested on orders pending audit.',
            ]);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['advance_amount' => 'Enter a valid advance amount.']);
        }
        if ($amount > (float) $order->total + 0.01) {
            throw ValidationException::withMessages(['advance_amount' => 'Advance cannot exceed order total.']);
        }

        return DB::transaction(function () use ($order, $actor, $amount, $notes) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->isPendingAudit()) {
                throw ValidationException::withMessages([
                    'order' => 'Advance can only be requested on orders pending audit.',
                ]);
            }

            $invoice = $this->invoices->createAdvanceInvoice($order, $amount, $actor);
            $from = $order->status;

            $order->update([
                'status' => Order::STATUS_AWAITING_ADVANCE,
                'advance_required' => true,
                'advance_amount' => $amount,
                'advance_invoice_id' => $invoice->id,
                'advance_requested_at' => now(),
                'advance_paid_at' => null,
                'audit_notes' => $notes,
                'audited_by' => $actor->id,
            ]);

            $this->recordHistory($order, $from, Order::STATUS_AWAITING_ADVANCE, 'advance_requested', $notes, [
                'advance_amount' => $amount,
                'advance_invoice_id' => $invoice->id,
            ], $actor);

            $this->auditLogger->log(
                'orders',
                'advance_requested',
                'Advance of ৳ '.number_format($amount, 2)." requested for {$order->number}",
                $order,
                ['status' => $from],
                ['status' => Order::STATUS_AWAITING_ADVANCE, 'advance_amount' => $amount],
                $actor,
            );

            return $order->fresh(['items.product', 'shop', 'salesman', 'advanceInvoice', 'statusHistories.user']);
        });
    }

    public function refreshAdvanceStatus(Order $order): Order
    {
        $order->loadMissing('advanceInvoice');

        if (! $order->advance_required || ! $order->advance_invoice_id) {
            return $order;
        }

        // Re-read invoice balances after payment apply.
        $order->unsetRelation('advanceInvoice');
        $order->load('advanceInvoice');

        if (! $order->isAdvancePaid()) {
            return $order->fresh(['advanceInvoice']);
        }

        if (! $order->advance_paid_at) {
            $order->update(['advance_paid_at' => now()]);

            $this->recordHistory(
                $order,
                $order->status,
                $order->status,
                'advance_paid',
                'Advance payment received — order is ready to approve.',
                [
                    'advance_amount' => (float) $order->advance_amount,
                    'invoice_id' => $order->advance_invoice_id,
                ],
                null,
            );

            $this->auditLogger->log(
                'orders',
                'advance_paid',
                "Advance paid for {$order->number} — ready to approve",
                $order,
                null,
                ['advance_amount' => (float) $order->advance_amount],
                null,
            );
        }

        return $order->fresh(['advanceInvoice']);
    }

    /**
     * Record (and auto-apply) the remaining advance for an order in one step.
     */
    public function collectAdvance(
        Order $order,
        User $actor,
        float $amount,
        string $method = \App\Models\Payment::METHOD_CASH,
        ?string $reference = null,
    ): Order {
        if (! $order->isAwaitingAdvance() || ! $order->advance_invoice_id) {
            throw ValidationException::withMessages([
                'order' => 'This order is not waiting for an advance payment.',
            ]);
        }

        $order->loadMissing('advanceInvoice');
        $invoice = $order->advanceInvoice;
        if (! $invoice) {
            throw ValidationException::withMessages(['advance' => 'Advance invoice missing.']);
        }

        $balance = (float) $invoice->balance;
        if ($balance <= 0.009) {
            return $this->refreshAdvanceStatus($order);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            $amount = $balance;
        }
        if ($amount > $balance + 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds advance balance (৳ '.number_format($balance, 2).').',
            ]);
        }

        app(\App\Services\PaymentService::class)->record([
            'shop_id' => $order->shop_id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'notes' => 'Advance for order '.$order->number,
        ], $actor);

        return $this->refreshAdvanceStatus($order->fresh(['advanceInvoice']));
    }

    public function approve(Order $order, User $actor, ?string $notes = null, bool $creditOverride = false): Order
    {
        if (! $order->canApproveNow()) {
            if ($order->isAwaitingAdvance() && ! $order->isAdvancePaid()) {
                throw ValidationException::withMessages([
                    'advance' => 'Advance payment is still due. Collect and verify the advance before approving.',
                ]);
            }

            throw ValidationException::withMessages([
                'order' => 'Only orders pending audit (or with advance paid) can be approved.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $notes, $creditOverride) {
            $order = Order::query()->lockForUpdate()->with(['items', 'shop', 'advanceInvoice'])->findOrFail($order->id);

            if (! $order->canApproveNow()) {
                throw ValidationException::withMessages([
                    'order' => 'Only orders pending audit (or with advance paid) can be approved.',
                ]);
            }
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
                'advance_amount' => (float) ($order->advance_amount ?: 0),
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
        if (! in_array($order->status, [Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE], true)) {
            throw ValidationException::withMessages([
                'order' => 'Only orders pending audit or awaiting advance can be rejected.',
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

    public function deleteBeforeApproval(Order $order, User $actor): void
    {
        if (! $order->canDeleteBeforeApproval()) {
            throw ValidationException::withMessages([
                'order' => 'Only orders waiting for Super Admin audit can be deleted.',
            ]);
        }

        DB::transaction(function () use ($order, $actor) {
            $order = Order::query()->lockForUpdate()->with(['items', 'shop'])->findOrFail($order->id);

            if (! $order->canDeleteBeforeApproval()) {
                throw ValidationException::withMessages([
                    'order' => 'Only orders waiting for Super Admin audit can be deleted.',
                ]);
            }

            $number = $order->number;
            $from = $order->status;

            $this->recordHistory($order, $from, $from, 'deleted_before_audit', 'Order deleted before approval', [
                'number' => $number,
                'total' => (float) $order->total,
            ], $actor);

            $this->auditLogger->log(
                'orders',
                'deleted',
                "Order {$number} deleted before approval",
                $order,
                ['status' => $from, 'total' => (float) $order->total],
                null,
                $actor,
            );

            $order->items()->delete();
            $order->delete();
        });
    }

    public function cancel(Order $order, User $actor, string $reason): Order
    {
        if (! in_array($order->status, [Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE, Order::STATUS_APPROVED], true)) {
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
