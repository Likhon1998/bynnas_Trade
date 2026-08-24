<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private InvoiceService $invoices,
        private CreditService $credit,
        private CommissionService $commissions,
    ) {}

    public function record(array $data, ?User $actor = null): Payment
    {
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be positive.']);
        }

        return DB::transaction(function () use ($data, $amount, $actor) {
            $payment = Payment::query()->create([
                'number' => $this->nextNumber(),
                'shop_id' => $data['shop_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'amount' => $amount,
                'method' => $data['method'] ?? Payment::METHOD_BANK,
                'reference' => $data['reference'] ?? null,
                'status' => Payment::STATUS_PENDING,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
            ]);

            $this->auditLogger->log(
                'payments',
                'recorded',
                "Payment {$payment->number} recorded",
                $payment,
                null,
                ['amount' => $amount, 'shop_id' => $payment->shop_id],
                $actor,
            );

            $fresh = $payment->fresh(['shop', 'invoice']);

            DB::afterCommit(function () use ($fresh) {
                try {
                    app(AppNotificationService::class)->paymentPending($fresh);
                } catch (\Throwable) {
                    // non-blocking
                }
            });

            return $fresh;
        });
    }

    public function verify(Payment $payment, ?User $actor = null): Payment
    {
        if ($payment->status === Payment::STATUS_VERIFIED) {
            return $payment;
        }

        if ($payment->status === Payment::STATUS_REJECTED) {
            throw ValidationException::withMessages(['payment' => 'Rejected payment cannot be verified.']);
        }

        return DB::transaction(function () use ($payment, $actor) {
            $payment = Payment::query()->lockForUpdate()->with(['invoice', 'shop'])->findOrFail($payment->id);

            if ($payment->invoice_id) {
                $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
                if ($payment->amount > ((float) $invoice->balance + 0.01)) {
                    throw ValidationException::withMessages([
                        'amount' => 'Payment exceeds invoice balance (৳ '.number_format($invoice->balance, 2).').',
                    ]);
                }
                $this->invoices->applyPayment($invoice, (float) $payment->amount);
            }

            $payment->update([
                'status' => Payment::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => $actor?->id,
            ]);

            $this->credit->recalculateOutstanding($payment->shop);

            $this->commissions->accrueFromPayment($payment->fresh(['invoice.order', 'invoice.shop']), $actor);

            $this->auditLogger->log(
                'payments',
                'verified',
                "Payment {$payment->number} verified",
                $payment,
                null,
                ['amount' => (float) $payment->amount],
                $actor,
            );

            return $payment->fresh(['shop', 'invoice', 'verifier']);
        });
    }

    public function reject(Payment $payment, string $reason, ?User $actor = null): Payment
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            throw ValidationException::withMessages(['payment' => 'Only pending payments can be rejected.']);
        }

        $payment->update([
            'status' => Payment::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'verified_by' => $actor?->id,
            'verified_at' => now(),
        ]);

        $this->auditLogger->log('payments', 'rejected', "Payment {$payment->number} rejected", $payment, null, ['reason' => $reason], $actor);

        return $payment->fresh();
    }

    public function nextNumber(): string
    {
        $seq = Payment::withTrashed()->count() + 1;

        return 'PAY-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
