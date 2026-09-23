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
            $order = Order::query()->lockForUpdate()->with(['items', 'shop', 'advanceInvoice'])->findOrFail($order->id);

            if ($order->invoice_id) {
                return Invoice::query()->findOrFail($order->invoice_id);
            }

            $advancePaid = 0.0;
            if ($order->advance_invoice_id && $order->advanceInvoice) {
                $advancePaid = min(
                    (float) ($order->advance_amount ?: 0),
                    (float) $order->advanceInvoice->paid_amount
                );
            }

            $remaining = max(0, round((float) $order->total - $advancePaid, 2));
            $terms = (int) ($order->shop->payment_terms_days ?: 21);
            $notes = 'Auto-raised from order '.$order->number;
            if ($advancePaid > 0) {
                $notes .= ' · Advance already paid ৳ '.number_format($advancePaid, 2);
            }

            $invoice = Invoice::query()->create([
                'number' => $this->nextNumber(),
                'shop_id' => $order->shop_id,
                'order_id' => $order->id,
                'status' => $remaining <= 0.009 ? Invoice::STATUS_PAID : Invoice::STATUS_ISSUED,
                'subtotal' => $remaining,
                'discount_total' => 0,
                'total' => $remaining,
                'paid_amount' => $remaining <= 0.009 ? $remaining : 0,
                'balance' => $remaining <= 0.009 ? 0 : $remaining,
                'issued_at' => now(),
                'due_at' => now()->addDays($terms)->toDateString(),
                'notes' => $notes,
                'created_by' => $actor?->id,
            ]);

            if ($remaining > 0.009) {
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
            } else {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => null,
                    'product_name' => 'Fully covered by advance on '.$order->number,
                    'product_sku' => 'ADVANCE',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'line_total' => 0,
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
                ['total' => (float) $invoice->total, 'shop_id' => $invoice->shop_id, 'advance_applied' => $advancePaid],
                $actor,
            );

            return $invoice->fresh(['items', 'shop', 'order']);
        });
    }

    public function createAdvanceInvoice(Order $order, float $amount, ?User $actor = null): Invoice
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['advance_amount' => 'Advance amount must be positive.']);
        }

        if ($amount > (float) $order->total + 0.01) {
            throw ValidationException::withMessages(['advance_amount' => 'Advance cannot exceed order total.']);
        }

        return DB::transaction(function () use ($order, $amount, $actor) {
            $order = Order::query()->lockForUpdate()->with('shop')->findOrFail($order->id);

            if ($order->advance_invoice_id) {
                return Invoice::query()->findOrFail($order->advance_invoice_id);
            }

            $invoice = Invoice::query()->create([
                'number' => $this->nextNumber(),
                'shop_id' => $order->shop_id,
                'order_id' => $order->id,
                'status' => Invoice::STATUS_ISSUED,
                'subtotal' => $amount,
                'discount_total' => 0,
                'total' => $amount,
                'paid_amount' => 0,
                'balance' => $amount,
                'issued_at' => now(),
                'due_at' => now()->toDateString(),
                'notes' => 'Advance payment required before approving order '.$order->number,
                'created_by' => $actor?->id,
            ]);

            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'product_id' => null,
                'product_name' => 'Advance on order '.$order->number,
                'product_sku' => 'ADVANCE',
                'quantity' => 1,
                'unit_price' => $amount,
                'line_total' => $amount,
            ]);

            $this->credit->recalculateOutstanding($order->shop);

            $this->auditLogger->log(
                'invoices',
                'advance_issued',
                "Advance invoice {$invoice->number} for {$order->number}",
                $invoice,
                null,
                ['amount' => $amount, 'order_id' => $order->id],
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
