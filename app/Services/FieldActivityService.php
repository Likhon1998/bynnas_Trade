<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FieldActivityService
{
    public const PERIODS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        'month' => 'This month',
        'all' => 'All time',
    ];

    public function periodStart(string $period): ?Carbon
    {
        return match ($period) {
            'today' => today(),
            '7d' => today()->subDays(6),
            'month' => now()->startOfMonth(),
            default => null,
        };
    }

    /**
     * Per-salesman visits, unique shops, orders and shops added since $from (all time when null).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summary(?Carbon $from, ?int $salesmanId = null): Collection
    {
        // "With order" only counts orders that are still alive (not rejected or cancelled).
        $visitStats = ShopVisit::query()
            ->leftJoin('orders as o', function ($join) {
                $join->on('o.id', '=', 'shop_visits.order_id')
                    ->whereNotIn('o.status', [Order::STATUS_REJECTED, Order::STATUS_CANCELLED]);
            })
            ->when($from, fn ($q) => $q->where('shop_visits.checked_in_at', '>=', $from))
            ->when($salesmanId, fn ($q) => $q->where('shop_visits.salesman_id', $salesmanId))
            ->selectRaw('shop_visits.salesman_id, COUNT(*) as visits, COUNT(DISTINCT shop_visits.shop_id) as shops, SUM(CASE WHEN o.id IS NOT NULL THEN 1 ELSE 0 END) as with_order, MAX(shop_visits.checked_in_at) as last_at')
            ->groupBy('shop_visits.salesman_id')
            ->get()
            ->keyBy('salesman_id');

        $orderStats = Order::query()
            ->whereNotNull('salesman_id')
            ->whereNotIn('status', [Order::STATUS_REJECTED, Order::STATUS_CANCELLED, Order::STATUS_DRAFT])
            ->when($from, fn ($q) => $q->where('submitted_at', '>=', $from))
            ->when($salesmanId, fn ($q) => $q->where('salesman_id', $salesmanId))
            ->selectRaw('salesman_id, COUNT(*) as orders, SUM(total) as order_value')
            ->groupBy('salesman_id')
            ->get()
            ->keyBy('salesman_id');

        $salesmen = User::query()
            ->with('salesmanProfile.territory')
            ->where('portal', User::PORTAL_SALESMAN)
            ->when($salesmanId, fn ($q) => $q->whereKey($salesmanId))
            ->orderBy('name')
            ->get();

        $addedStats = Shop::query()
            ->whereIn('created_by', $salesmen->modelKeys())
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->selectRaw('created_by, COUNT(*) as added, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as added_pending', [Shop::STATUS_PENDING])
            ->groupBy('created_by')
            ->get()
            ->keyBy('created_by');

        return $salesmen
            ->filter(fn (User $u) => $salesmanId || $u->is_active || isset($visitStats[$u->id]) || isset($orderStats[$u->id]) || isset($addedStats[$u->id]))
            ->map(function (User $u) use ($visitStats, $orderStats, $addedStats) {
                $v = $visitStats[$u->id] ?? null;
                $o = $orderStats[$u->id] ?? null;
                $a = $addedStats[$u->id] ?? null;
                $visits = (int) ($v->visits ?? 0);
                $withOrder = (int) ($v->with_order ?? 0);

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'code' => $u->salesmanProfile?->employee_code,
                    'territory' => $u->salesmanProfile?->territory?->name,
                    'visits' => $visits,
                    'shops' => (int) ($v->shops ?? 0),
                    'with_order' => $withOrder,
                    'conversion' => $visits > 0 ? (int) round($withOrder / $visits * 100) : null,
                    'orders' => (int) ($o->orders ?? 0),
                    'order_value' => (float) ($o->order_value ?? 0),
                    'added' => (int) ($a->added ?? 0),
                    'added_pending' => (int) ($a->added_pending ?? 0),
                    'last_at' => $v?->last_at ? Carbon::parse($v->last_at) : null,
                ];
            })
            ->sortBy([['visits', 'desc'], ['order_value', 'desc'], ['name', 'asc']])
            ->values();
    }

    /** Distinct shops visited by anyone (a shop seen by two salesmen counts once). */
    public function distinctShopsVisited(?Carbon $from): int
    {
        return (int) ShopVisit::query()
            ->when($from, fn ($q) => $q->where('checked_in_at', '>=', $from))
            ->distinct()
            ->count('shop_id');
    }
}
