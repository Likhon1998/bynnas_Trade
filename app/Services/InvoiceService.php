<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private CreditService $credit,
    ) {}

    public function createFromOrder(Order $order, ?User $actor = null): Invoice
    {
        if ($order->invoice_id) {
            return Invoice::query()->findOrFail($order->invoice_id);
        }

        if (! in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_DISPATCHED, Order::STATUS_APPROVED, Order::STATUS_PACKED, Order::STATUS_PICKED], true)) {
            throw ValidationException::withMessages([
                'order' => 'Invoice can only be raised for approved or fulfilled orders.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor) {
            $order = Order::query()->lockForUpdate()->with(['items', 'shop'])->findOrFail($order->id);

            if ($order->invoice_id) {
                return Invoice::query()->findOrFail($order->invoice_id);
            }

            $terms = (int) ($order->shop->payment_terms_days ?: 21);

            $invoice = Invoice::query()->create([
                'number' => $this->nextNumber(),
                'shop_id' => $order->shop_id,
                'order_id' => $order->id,
                'status' => Invoice::STATUS_ISSUED,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'total' => $order->total,
                'paid_amount' => 0,
                'balance' => $order->total,
                'issued_at' => now(),
                'due_at' => now()->addDays($terms)->toDateString(),
                'notes' => 'Auto-raised from order '.$order->number,
                'created_by' => $actor?->id,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
            }

            $order->update(['invoice_id' => $invoice->id]);

            $this->credit->recalculateOutstanding($order->shop);

            $this->auditLogger->log(
                'invoices',
                'issued',
                "Invoice {$invoice->number} issued for {$order->number}",
                $invoice,
                null,
                ['total' => (float) $invoice->total, 'shop_id' => $invoice->shop_id],
                $actor,
            );

            return $invoice->fresh(['items', 'shop', 'order']);
        });
    }

    public function applyPayment(Invoice $invoice, float $amount): Invoice
    {
        $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
        $paid = round((float) $invoice->paid_amount + $amount, 2);
        $balance = max(0, round((float) $invoice->total - $paid, 2));

        $status = $balance <= 0.009
            ? Invoice::STATUS_PAID
            : ($paid > 0 ? Invoice::STATUS_PARTIAL : Invoice::STATUS_ISSUED);

        $invoice->update([
            'paid_amount' => min($paid, (float) $invoice->total),
            'balance' => $balance,
            'status' => $status,
        ]);

        return $invoice->fresh();
    }

    public function nextNumber(): string
    {
        $seq = Invoice::withTrashed()->count() + 1;

        return 'INV-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
