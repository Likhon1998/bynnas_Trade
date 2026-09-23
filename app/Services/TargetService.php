<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SalesTarget;
use App\Models\SalesmanProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TargetService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private RewardService $rewards,
    ) {}

    public function ensureForSalesman(User $salesman, int $year, int $month, ?User $actor = null): SalesTarget
    {
        $profile = $salesman->salesmanProfile;
        $existing = SalesTarget::query()
            ->where('salesman_id', $salesman->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existing) {
            return $existing;
        }

        return SalesTarget::query()->create([
            'salesman_id' => $salesman->id,
            'territory_id' => $profile?->territory_id,
            'year' => $year,
            'month' => $month,
            'target_amount' => (float) ($profile?->monthly_target ?? 0),
            'achieved_amount' => 0,
            'collected_amount' => 0,
            'status' => SalesTarget::STATUS_OPEN,
            'target_met' => false,
            'created_by' => $actor?->id,
        ]);
    }

    public function upsert(array $data, ?User $actor = null): SalesTarget
    {
        $salesmanId = (int) $data['salesman_id'];
        $year = (int) $data['year'];
        $month = (int) $data['month'];

        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages(['month' => 'Month must be 1–12.']);
        }

        $target = SalesTarget::query()->updateOrCreate(
            ['salesman_id' => $salesmanId, 'year' => $year, 'month' => $month],
            [
                'territory_id' => $data['territory_id'] ?? User::query()->find($salesmanId)?->salesmanProfile?->territory_id,
                'target_amount' => (float) $data['target_amount'],
                'created_by' => $actor?->id,
            ],
        );

        $this->recalculate($target);

        $this->auditLogger->log(
            'targets',
            'upserted',
            "Target {$target->periodLabel()} for salesman #{$salesmanId}",
            $target,
            null,
            ['target_amount' => (float) $target->target_amount],
            $actor,
        );

        return $target->fresh(['salesman', 'territory']);
    }

    public function recalculate(SalesTarget $target): SalesTarget
    {
        $start = now()->setDate($target->year, $target->month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $achieved = (float) Order::query()
            ->where('salesman_id', $target->salesman_id)
            ->where('status', Order::STATUS_DELIVERED)
            ->where(function ($q) use ($start, $end) {
                $q->whereHas('statusHistories', function ($h) use ($start, $end) {
                    $h->where('to_status', Order::STATUS_DELIVERED)
                        ->whereBetween('created_at', [$start, $end]);
                })->orWhere(function ($o) use ($start, $end) {
                    $o->whereDoesntHave('statusHistories', fn ($h) => $h->where('to_status', Order::STATUS_DELIVERED))
                        ->whereBetween('submitted_at', [$start, $end]);
                });
            })
            ->sum('total');

        $collected = (float) Payment::query()
            ->where('status', Payment::STATUS_VERIFIED)
            ->whereBetween('verified_at', [$start, $end])
            ->where(function ($q) use ($target) {
                $sid = $target->salesman_id;

                // Prefer order salesman when present (exclusive attribution).
                $q->whereHas('invoice.order', fn ($o) => $o->where('salesman_id', $sid))
                    ->orWhere(function ($fallback) use ($sid) {
                        // Shop / payment shop only when the linked order has no salesman.
                        $fallback->where(function ($noOrderSalesman) {
                            $noOrderSalesman
                                ->whereDoesntHave('invoice')
                                ->orWhereHas('invoice', function ($inv) {
                                    $inv->where(function ($i) {
                                        $i->whereNull('order_id')
                                            ->orWhereHas('order', fn ($o) => $o->whereNull('salesman_id'));
                                    });
                                });
                        })->where(function ($shopAttr) use ($sid) {
                            $shopAttr
                                ->whereHas('invoice.shop', fn ($s) => $s->where('assigned_salesman_id', $sid))
                                ->orWhereHas('shop', fn ($s) => $s->where('assigned_salesman_id', $sid));
                        });
                    });
            })
            ->sum('amount');

        $met = (float) $target->target_amount > 0 && $achieved >= (float) $target->target_amount;

        $target->update([
            'achieved_amount' => round($achieved, 2),
            'collected_amount' => round($collected, 2),
            'target_met' => $met,
        ]);

        if ($met) {
            $this->rewards->ensureTargetHitReward($target->fresh());
        }

        return $target->fresh();
    }

    public function recalculatePeriod(int $year, int $month): void
    {
        SalesTarget::query()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->each(fn (SalesTarget $t) => $this->recalculate($t));
    }

    public function seedCurrentMonthFromProfiles(?User $actor = null): int
    {
        $year = (int) now()->year;
        $month = (int) now()->month;
        $count = 0;

        SalesmanProfile::query()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->each(function (SalesmanProfile $profile) use ($year, $month, $actor, &$count) {
                if (! $profile->user) {
                    return;
                }
                $this->ensureForSalesman($profile->user, $year, $month, $actor);
                $count++;
            });

        $this->recalculatePeriod($year, $month);

        return $count;
    }
}
