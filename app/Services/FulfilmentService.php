<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderFulfilment;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfilmentService
{
    public function __construct(
        private InventoryService $inventory,
        private AuditLogger $auditLogger,
        private InvoiceService $invoices,
    ) {}

    public function createForApprovedOrder(Order $order, ?Warehouse $warehouse = null, ?User $actor = null): OrderFulfilment
    {
        $warehouse ??= Warehouse::defaultWarehouse();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse' => 'No active warehouse configured.',
            ]);
        }

        $order->update(['warehouse_id' => $warehouse->id]);

        return OrderFulfilment::query()->create([
            'order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'status' => OrderFulfilment::STATUS_AWAITING_PICK,
            'warehouse_notes' => 'Auto-queued after Super Admin approval',
        ]);
    }

    public function startPick(OrderFulfilment $fulfilment, User $actor): OrderFulfilment
    {
        $this->assertStatus($fulfilment, [OrderFulfilment::STATUS_AWAITING_PICK]);

        return DB::transaction(function () use ($fulfilment, $actor) {
            $fulfilment->update([
                'status' => OrderFulfilment::STATUS_PICKING,
                'picker_id' => $actor->id,
                'picking_started_at' => now(),
            ]);

            $order = $fulfilment->order;
            $from = $order->status;
            $order->update(['status' => Order::STATUS_PICKING]);
            $this->history($order, $from, Order::STATUS_PICKING, 'picking_started', null, $actor);

            return $fulfilment->fresh(['order', 'warehouse', 'picker']);
        });
    }

    public function completePick(OrderFulfilment $fulfilment, User $actor, ?string $notes = null): OrderFulfilment
    {
        $this->assertStatus($fulfilment, [OrderFulfilment::STATUS_PICKING, OrderFulfilment::STATUS_AWAITING_PICK]);

        return DB::transaction(function () use ($fulfilment, $actor, $notes) {
            $fulfilment = OrderFulfilment::query()->lockForUpdate()->with(['order.items.product', 'warehouse'])->findOrFail($fulfilment->id);
            $order = $fulfilment->order;
            $warehouse = $fulfilment->warehouse;

            if ($fulfilment->status === OrderFulfilment::STATUS_AWAITING_PICK) {
                $fulfilment->update([
                    'picker_id' => $actor->id,
                    'picking_started_at' => now(),
                ]);
            }

            foreach ($order->items as $item) {
                if (! $item->product_id || $item->reserved_quantity <= 0) {
                    continue;
                }
                $this->inventory->pick($warehouse, $item->product, (int) $item->reserved_quantity, $order, $actor);
                $item->update(['reserved_quantity' => 0]);
            }

            $order->update(['stock_reserved' => false]);

            $fulfilment->update([
                'status' => OrderFulfilment::STATUS_PICKED,
                'picker_id' => $actor->id,
                'picked_at' => now(),
                'warehouse_notes' => $notes ?: $fulfilment->warehouse_notes,
            ]);

            $from = $order->status;
            $order->update(['status' => Order::STATUS_PICKED]);
            $this->history($order, $from, Order::STATUS_PICKED, 'picked', $notes, $actor);

            $this->auditLogger->log('fulfilment', 'picked', "Order {$order->number} picked at {$warehouse->code}", $order, null, null, $actor);

            return $fulfilment->fresh(['order.items', 'warehouse', 'picker']);
        });
    }

    public function pack(OrderFulfilment $fulfilment, User $actor, ?string $notes = null): OrderFulfilment
    {
        $this->assertStatus($fulfilment, [OrderFulfilment::STATUS_PICKED]);

        return DB::transaction(function () use ($fulfilment, $actor, $notes) {
            $fulfilment->update([
                'status' => OrderFulfilment::STATUS_PACKED,
                'packer_id' => $actor->id,
                'packed_at' => now(),
                'warehouse_notes' => $notes ?: $fulfilment->warehouse_notes,
            ]);

            $order = $fulfilment->order;
            $from = $order->status;
            $order->update(['status' => Order::STATUS_PACKED]);
            $this->history($order, $from, Order::STATUS_PACKED, 'packed', $notes, $actor);

            return $fulfilment->fresh(['order', 'packer']);
        });
    }

    public function dispatch(OrderFulfilment $fulfilment, User $actor, array $deliveryData = []): Delivery
    {
        $this->assertStatus($fulfilment, [OrderFulfilment::STATUS_PACKED]);

        return DB::transaction(function () use ($fulfilment, $actor, $deliveryData) {
            $fulfilment = OrderFulfilment::query()->lockForUpdate()->with(['order.shop', 'warehouse'])->findOrFail($fulfilment->id);
            $order = $fulfilment->order;
            $shop = $order->shop;

            $fulfilment->update([
                'status' => OrderFulfilment::STATUS_DISPATCHED,
                'dispatcher_id' => $actor->id,
                'dispatched_at' => now(),
            ]);

            $delivery = Delivery::query()->create([
                'number' => $this->nextDeliveryNumber(),
                'order_id' => $order->id,
                'fulfilment_id' => $fulfilment->id,
                'warehouse_id' => $fulfilment->warehouse_id,
                'shop_id' => $shop->id,
                'assigned_to' => $deliveryData['assigned_to'] ?? null,
                'status' => Delivery::STATUS_OUT_FOR_DELIVERY,
                'tracking_ref' => $deliveryData['tracking_ref'] ?? null,
                'delivery_address' => $deliveryData['delivery_address'] ?? trim(($shop->address ?? '').', '.($shop->city ?? ''), ', '),
                'notes' => $deliveryData['notes'] ?? null,
                'dispatched_at' => now(),
                'created_by' => $actor->id,
            ]);

            $from = $order->status;
            $order->update(['status' => Order::STATUS_DISPATCHED]);
            $this->history($order, $from, Order::STATUS_DISPATCHED, 'dispatched', $delivery->number, $actor);

            $this->auditLogger->log('fulfilment', 'dispatched', "Order {$order->number} dispatched as {$delivery->number}", $order, null, null, $actor);

            return $delivery->fresh(['order', 'shop', 'assignee', 'fulfilment']);
        });
    }

    public function markDelivered(Delivery $delivery, User $actor, ?string $notes = null): Delivery
    {
        if ($delivery->status === Delivery::STATUS_DELIVERED) {
            throw ValidationException::withMessages(['delivery' => 'Already delivered.']);
        }

        return DB::transaction(function () use ($delivery, $actor, $notes) {
            $delivery = Delivery::query()->lockForUpdate()->with(['fulfilment', 'order'])->findOrFail($delivery->id);

            $delivery->update([
                'status' => Delivery::STATUS_DELIVERED,
                'delivered_at' => now(),
                'notes' => $notes ?: $delivery->notes,
            ]);

            $fulfilment = $delivery->fulfilment;
            $fulfilment->update([
                'status' => OrderFulfilment::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            $order = $delivery->order;
            $from = $order->status;
            $order->update(['status' => Order::STATUS_DELIVERED]);
            $this->history($order, $from, Order::STATUS_DELIVERED, 'delivered', $notes, $actor);

            if (! $order->invoice_id) {
                $this->invoices->createFromOrder($order->fresh('items'), $actor);
            }

            $this->auditLogger->log('deliveries', 'delivered', "Delivery {$delivery->number} completed", $delivery, null, null, $actor);

            return $delivery->fresh(['order.invoice', 'shop', 'fulfilment']);
        });
    }

    public function nextDeliveryNumber(): string
    {
        $seq = Delivery::query()->count() + 1;

        return 'DLV-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function assertStatus(OrderFulfilment $fulfilment, array $allowed): void
    {
        if (! in_array($fulfilment->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'fulfilment' => 'Invalid fulfilment step for status '.$fulfilment->statusLabel().'.',
            ]);
        }
    }

    private function history(Order $order, ?string $from, string $to, string $event, ?string $notes, ?User $actor): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'event' => $event,
            'notes' => $notes,
            'user_id' => $actor?->id,
        ]);
    }
}
