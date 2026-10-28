<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CollectionSummaryService
{
    /**
     * Money received from each shop / owner.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byShop(User $viewer, ?string $search = null): Collection
    {
        $shops = $this->shopsFor($viewer)
            ->when($search, function ($q, $search) {
                $term = '%'.$search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('owner_name', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();

        $shopIds = $shops->pluck('id');
        if ($shopIds->isEmpty()) {
            return collect();
        }

        $verified = Payment::STATUS_VERIFIED;
        $pending = Payment::STATUS_PENDING;
        $viewerId = (int) $viewer->id;

        $totals = Payment::query()
            ->whereIn('shop_id', $shopIds)
            ->whereIn('status', [$verified, $pending])
            ->selectRaw("
                shop_id,
                SUM(CASE WHEN status = '{$verified}' THEN amount ELSE 0 END) as received,
                SUM(CASE WHEN status = '{$pending}' THEN amount ELSE 0 END) as pending,
                SUM(CASE WHEN status = '{$verified}' AND created_by = {$viewerId} THEN amount ELSE 0 END) as you_collected,
                COUNT(*) as receipts,
                MAX(COALESCE(verified_at, paid_at, created_at)) as last_at
            ")
            ->groupBy('shop_id')
            ->get()
            ->keyBy('shop_id');

        return $shops
            ->map(function (Shop $shop) use ($totals) {
                $row = $totals->get($shop->id);

                return [
                    'shop' => $shop,
                    'person' => $shop->owner_name ?: $shop->name,
                    'received' => round((float) ($row->received ?? 0), 2),
                    'pending' => round((float) ($row->pending ?? 0), 2),
                    'you_collected' => round((float) ($row->you_collected ?? 0), 2),
                    'baki' => round((float) $shop->outstanding_balance, 2),
                    'receipts' => (int) ($row->receipts ?? 0),
                    'last_at' => ! empty($row?->last_at) ? Carbon::parse($row->last_at) : null,
                ];
            })
            ->filter(fn (array $row) => $row['received'] > 0.009 || $row['pending'] > 0.009 || $row['baki'] > 0.009)
            ->sortByDesc('received')
            ->values();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function receipts(User $viewer, int $limit = 80): Collection
    {
        $shopIds = $this->shopsFor($viewer)->pluck('id');
        if ($shopIds->isEmpty()) {
            return collect();
        }

        return Payment::query()
            ->with(['shop', 'creator', 'invoice'])
            ->whereIn('shop_id', $shopIds)
            ->whereIn('status', [Payment::STATUS_VERIFIED, Payment::STATUS_PENDING])
            ->latest('paid_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function shopsFor(User $viewer)
    {
        if ($viewer->portal === User::PORTAL_SALESMAN) {
            return Shop::query()->where('status', Shop::STATUS_ACTIVE);
        }

        return Shop::query()->visibleTo($viewer);
    }
}
