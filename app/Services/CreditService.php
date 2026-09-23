<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProductReturn;
use App\Models\Shop;

class CreditService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function recalculateOutstanding(Shop $shop): Shop
    {
        $openInvoices = (float) Invoice::query()
            ->where('shop_id', $shop->id)
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
            ->sum('balance');

        $returnCredits = (float) ProductReturn::query()
            ->where('shop_id', $shop->id)
            ->where('status', ProductReturn::STATUS_APPROVED)
            ->where('credit_issued', true)
            ->sum('total');

        $shop->update([
            'outstanding_balance' => max(0, round($openInvoices - $returnCredits, 2)),
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

    public function enforceCreditHold(Shop $shop): Shop
    {
        // Credit limit is informational only — shops are not blocked from buying.
        return $shop;
    }

    public function assertCanOrder(Shop $shop, float $orderTotal, bool $override = false): void
    {
        // No available-balance gate: any shop may order any quantity.
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
