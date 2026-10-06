<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductReturn;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function recalculateOutstanding(Shop $shop): Shop
    {
        $openInvoices = (float) Invoice::query()
            ->where('shop_id', $shop->id)
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
            ->sum('balance');

        $shop->update([
            'outstanding_balance' => max(0, round($openInvoices - $this->unappliedCredit($shop), 2)),
        ]);

        $this->enforceCreditHold($shop->fresh());

        return $shop->fresh();
    }

    public function adjustOutstanding(Shop $shop, float $delta, ?string $reason = null, $actor = null): Shop
    {
        $shop->refresh();
        $new = max(0, round((float) $shop->outstanding_balance + $delta, 2));
        $shop->update(['outstanding_balance' => $new]);

        $this->auditLogger->log(
            'credit',
            'adjusted',
            "Shop {$shop->code} outstanding {$shop->outstanding_balance} ({$reason})",
            $shop,
            null,
            ['delta' => $delta, 'outstanding' => $new],
            $actor,
        );

        return $this->enforceCreditHold($shop->fresh());
    }

    /** Money the shop has paid or been credited that is not tied to an invoice. */
    public function unappliedCredit(Shop $shop): float
    {
        $returnCredits = (float) ProductReturn::query()
            ->where('shop_id', $shop->id)
            ->where('status', ProductReturn::STATUS_APPROVED)
            ->where('credit_issued', true)
            ->sum(DB::raw('total - applied_to_invoice'));

        $unallocatedPayments = (float) Payment::query()
            ->where('shop_id', $shop->id)
            ->where('status', Payment::STATUS_VERIFIED)
            ->whereNull('invoice_id')
            ->sum('amount');

        return round($returnCredits + $unallocatedPayments, 2);
    }

    /**
     * The shop is never put on hold automatically; the credit limit is enforced when an order is
     * approved (see creditCheck / assertCanOrder).
     */
    public function enforceCreditHold(Shop $shop): Shop
    {
        return $shop;
    }

    /**
     * Owed now (open invoices) plus owed soon (approved orders not invoiced yet), excluding one order.
     */
    public function exposure(Shop $shop, ?int $exceptOrderId = null): float
    {
        $pipeline = Order::query()
            ->where('shop_id', $shop->id)
            ->whereIn('status', [
                Order::STATUS_APPROVED, Order::STATUS_PICKING, Order::STATUS_PICKED,
                Order::STATUS_PACKED, Order::STATUS_DISPATCHED, Order::STATUS_DELIVERED,
            ])
            ->whereNull('invoice_id')
            ->when($exceptOrderId, fn ($q) => $q->whereKeyNot($exceptOrderId))
            ->with('advanceInvoice')
            ->get()
            ->sum(fn (Order $order) => $order->settlement()['remaining_due']);

        return round((float) $shop->outstanding_balance + $pipeline, 2);
    }

    /**
     * @return array{limit: float, exposure: float, available: float, needed: float, ok: bool}
     */
    public function creditCheck(Order $order): array
    {
        $shop = $order->shop;
        $limit = round((float) $shop->credit_limit, 2);
        $exposure = $this->exposure($shop, $order->id);
        $available = max(0, round($limit - $exposure, 2));
        $needed = round($order->settlement()['remaining_due'], 2);

        return [
            'limit' => $limit,
            'exposure' => $exposure,
            'available' => $available,
            'needed' => $needed,
            'ok' => $needed <= $available + 0.009,
        ];
    }

    /**
     * Block an approval that would take the shop past its credit limit unless an authorised
     * user overrides it. Returns true when the override was used.
     */
    public function assertCanOrder(Order $order, User $actor, bool $override = false): bool
    {
        $check = $this->creditCheck($order);
        if ($check['ok']) {
            return false;
        }

        $taka = fn (float $v) => '৳ '.number_format($v, 2);
        if (! $override) {
            throw ValidationException::withMessages([
                'credit' => "Over credit limit: this order needs {$taka($check['needed'])} of credit but only {$taka($check['available'])} is available "
                    ."(limit {$taka($check['limit'])}, already owed {$taka($check['exposure'])}). Request an advance, or tick \"Override credit limit\".",
            ]);
        }

        if (! $actor->can('orders.credit_override')) {
            throw ValidationException::withMessages([
                'credit' => 'You are not allowed to approve orders over the credit limit. Ask an admin.',
            ]);
        }

        return true;
    }

    public function syncAllShops(): void
    {
        Shop::query()->chunkById(50, function ($shops) {
            foreach ($shops as $shop) {
                $this->recalculateOutstanding($shop);
            }
        });
    }
}
