<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProductReturn;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShopLedgerService
{
    /**
     * @return array{
     *   shop: Shop,
     *   summary: array<string, mixed>,
     *   entries: Collection<int, array<string, mixed>>
     * }
     */
    public function statement(Shop $shop): array
    {
        $invoices = Invoice::query()
            ->where('shop_id', $shop->id)
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID])
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();

        $payments = Payment::query()
            ->where('shop_id', $shop->id)
            ->where('status', Payment::STATUS_VERIFIED)
            ->orderBy('verified_at')
            ->orderBy('id')
            ->get();

        $returns = ProductReturn::query()
            ->where('shop_id', $shop->id)
            ->where('status', ProductReturn::STATUS_APPROVED)
            ->where('credit_issued', true)
            ->orderBy('approved_at')
            ->orderBy('id')
            ->get();

        $raw = collect();

        foreach ($invoices as $invoice) {
            $raw->push([
                'at' => $invoice->issued_at ?? $invoice->created_at,
                'sort' => 1,
                'type' => 'invoice',
                'description' => 'Purchase · '.($invoice->number ?: 'Invoice #'.$invoice->id),
                'debit' => (float) $invoice->total,
                'credit' => 0.0,
                'status' => $this->invoiceStatusLabel($invoice),
                'ref' => $invoice->number,
            ]);
        }

        foreach ($payments as $payment) {
            $raw->push([
                'at' => $payment->verified_at ?? $payment->created_at,
                'sort' => 2,
                'type' => 'payment',
                'description' => 'Payment · '.($payment->number ?: 'Payment #'.$payment->id)
                    .($payment->invoice_id ? ' (applied)' : ' (unapplied)'),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'status' => 'Paid',
                'ref' => $payment->number,
            ]);
        }

        foreach ($returns as $return) {
            $raw->push([
                'at' => $return->approved_at ?? $return->created_at,
                'sort' => 3,
                'type' => 'return_credit',
                'description' => 'Return credit · '.($return->number ?: 'Return #'.$return->id),
                'debit' => 0.0,
                'credit' => (float) $return->total,
                'status' => 'Credit',
                'ref' => $return->number,
            ]);
        }

        $entries = $raw->sortBy(fn ($row) => [
            optional($row['at'])->timestamp ?? 0,
            $row['sort'],
        ])->values();

        $running = 0.0;
        $entries = $entries->map(function (array $row) use (&$running) {
            $running = round($running + $row['debit'] - $row['credit'], 2);
            $row['balance'] = max(0, $running);

            return $row;
        });

        $totalPurchases = round((float) $invoices->sum('total'), 2);
        $totalPayments = round((float) $payments->sum('amount'), 2);
        $lastPayment = $payments->sortByDesc(fn (Payment $p) => $p->verified_at?->timestamp ?? 0)->first();

        return [
            'shop' => $shop,
            'summary' => [
                'shop_name' => $shop->name,
                'total_purchases' => $totalPurchases,
                'total_payments' => $totalPayments,
                'outstanding' => (float) $shop->outstanding_balance,
                'last_payment_amount' => $lastPayment ? (float) $lastPayment->amount : 0.0,
                'last_payment_date' => $lastPayment?->verified_at,
            ],
            'entries' => $entries,
        ];
    }

    private function invoiceStatusLabel(Invoice $invoice): string
    {
        return match ($invoice->status) {
            Invoice::STATUS_PAID => 'Paid',
            Invoice::STATUS_PARTIAL => 'Partial Paid',
            Invoice::STATUS_ISSUED => 'Due',
            default => ucfirst((string) $invoice->status),
        };
    }
}
