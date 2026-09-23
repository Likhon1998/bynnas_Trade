<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Payment;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private TargetService $targets,
    ) {}

    public function accrueFromPayment(Payment $payment, ?User $actor = null): ?Commission
    {
        if ($payment->status !== Payment::STATUS_VERIFIED) {
            return null;
        }

        $payment->loadMissing(['invoice.order.visit', 'invoice.shop', 'shop']);

        return DB::transaction(function () use ($payment, $actor) {
            $existing = Commission::query()
                ->where('payment_id', $payment->id)
                ->where('type', Commission::TYPE_COLLECTION)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing->load(['salesman', 'payment']);
            }

            $salesmanId = $this->resolveSalesmanId($payment);

            if (! $salesmanId) {
                $this->auditLogger->log(
                    'commissions',
                    'skipped',
                    "Commission skipped for {$payment->number} — no salesman on order/shop",
                    $payment,
                    null,
                    ['payment_id' => $payment->id, 'shop_id' => $payment->shop_id],
                    $actor,
                );

                return null;
            }

            $rule = CommissionRule::activeDefault();
            $rate = (float) ($rule?->collection_rate_percent ?? 3.5);
            $amount = round(((float) $payment->amount) * $rate / 100, 2);

            if ($amount <= 0) {
                return null;
            }

            $when = $payment->verified_at ?? now();
            $year = (int) $when->year;
            $month = (int) $when->month;
            $order = $payment->invoice?->order;

            $salesman = User::query()->findOrFail($salesmanId);
            $target = $this->targets->ensureForSalesman($salesman, $year, $month, $actor);

            $commission = Commission::query()->create([
                'number' => $this->nextNumber(),
                'salesman_id' => $salesmanId,
                'sales_target_id' => $target->id,
                'payment_id' => $payment->id,
                'order_id' => $order?->id,
                'invoice_id' => $payment->invoice_id,
                'type' => Commission::TYPE_COLLECTION,
                'year' => $year,
                'month' => $month,
                'base_amount' => $payment->amount,
                'rate_percent' => $rate,
                'commission_amount' => $amount,
                'status' => Commission::STATUS_ACCRUED,
                'notes' => 'Accrued on verified payment '.$payment->number,
                'created_by' => $actor?->id,
            ]);

            $this->targets->recalculate($target);
            $this->maybeAccrueTargetBonus($target->fresh(), $actor);

            $this->auditLogger->log(
                'commissions',
                'accrued',
                "Commission {$commission->number} accrued",
                $commission,
                null,
                ['amount' => $amount, 'payment_id' => $payment->id, 'salesman_id' => $salesmanId],
                $actor,
            );

            try {
                app(AppNotificationService::class)->commissionAccrued($commission->fresh(['salesman', 'payment']));
            } catch (\Throwable) {
                // non-blocking
            }

            return $commission->fresh(['salesman', 'payment']);
        });
    }

    /**
     * Accrue any missing collection commissions for verified payments.
     *
     * @return array{created:int, skipped:int, existing:int}
     */
    public function syncFromVerifiedPayments(?User $actor = null): array
    {
        $created = 0;
        $skipped = 0;
        $existing = 0;

        Payment::query()
            ->where('status', Payment::STATUS_VERIFIED)
            ->with(['invoice.order.visit', 'invoice.shop', 'shop'])
            ->orderBy('id')
            ->each(function (Payment $payment) use ($actor, &$created, &$skipped, &$existing) {
                $had = Commission::query()
                    ->where('payment_id', $payment->id)
                    ->where('type', Commission::TYPE_COLLECTION)
                    ->exists();

                $result = $this->accrueFromPayment($payment, $actor);

                if ($had) {
                    $existing++;
                } elseif ($result) {
                    $created++;
                } else {
                    $skipped++;
                }
            });

        return compact('created', 'skipped', 'existing');
    }

    public function maybeAccrueTargetBonus(SalesTarget $target, ?User $actor = null): ?Commission
    {
        return DB::transaction(function () use ($target, $actor) {
            $target = SalesTarget::query()->lockForUpdate()->findOrFail($target->id);

            if (! $target->target_met) {
                return null;
            }

            $existing = Commission::query()
                ->where('sales_target_id', $target->id)
                ->where('type', Commission::TYPE_TARGET_BONUS)
                ->whereNotIn('status', [Commission::STATUS_REJECTED])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $rule = CommissionRule::activeDefault();
            $rate = (float) ($rule?->target_bonus_percent ?? 1.0);
            $base = (float) $target->collected_amount;
            if ($base <= 0) {
                $base = (float) $target->achieved_amount;
            }
            $amount = round($base * $rate / 100, 2);
            if ($amount <= 0) {
                return null;
            }

            $commission = Commission::query()->create([
                'number' => $this->nextNumber(),
                'salesman_id' => $target->salesman_id,
                'sales_target_id' => $target->id,
                'type' => Commission::TYPE_TARGET_BONUS,
                'year' => $target->year,
                'month' => $target->month,
                'base_amount' => $base,
                'rate_percent' => $rate,
                'commission_amount' => $amount,
                'status' => Commission::STATUS_ACCRUED,
                'notes' => 'Target met bonus for '.$target->periodLabel(),
                'created_by' => $actor?->id,
            ]);

            $this->auditLogger->log('commissions', 'bonus', "Target bonus {$commission->number}", $commission, null, ['amount' => $amount], $actor);

            return $commission;
        });
    }

    public function approve(Commission $commission, ?User $actor = null): Commission
    {
        if ($commission->status !== Commission::STATUS_ACCRUED) {
            throw ValidationException::withMessages(['commission' => 'Only accrued commissions can be approved.']);
        }

        $commission->update([
            'status' => Commission::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $actor?->id,
        ]);

        $this->auditLogger->log('commissions', 'approved', "Commission {$commission->number} approved", $commission, null, null, $actor);

        return $commission->fresh();
    }

    public function markPaid(Commission $commission, ?User $actor = null): Commission
    {
        if ($commission->status !== Commission::STATUS_APPROVED) {
            throw ValidationException::withMessages(['commission' => 'Only approved commissions can be marked paid.']);
        }

        $commission->update([
            'status' => Commission::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $this->auditLogger->log('commissions', 'paid', "Commission {$commission->number} paid", $commission, null, null, $actor);

        return $commission->fresh();
    }

    public function reject(Commission $commission, string $reason, ?User $actor = null): Commission
    {
        if ($commission->status === Commission::STATUS_PAID) {
            throw ValidationException::withMessages(['commission' => 'Paid commissions cannot be rejected.']);
        }

        if ($commission->status === Commission::STATUS_REJECTED) {
            throw ValidationException::withMessages(['commission' => 'Commission is already rejected.']);
        }

        $commission->update([
            'status' => Commission::STATUS_REJECTED,
            'notes' => trim(($commission->notes ? $commission->notes."\n" : '').'Rejected: '.$reason),
            'approved_by' => $actor?->id,
            'approved_at' => now(),
        ]);

        $this->auditLogger->log('commissions', 'rejected', "Commission {$commission->number} rejected", $commission, null, ['reason' => $reason], $actor);

        return $commission->fresh();
    }

    /**
     * Order salesman → visit salesman → invoice shop → payment shop.
     */
    public function resolveSalesmanId(Payment $payment): ?int
    {
        $payment->loadMissing(['invoice.order.visit', 'invoice.shop', 'shop']);

        $order = $payment->invoice?->order;

        $id = $order?->salesman_id
            ?: $order?->visit?->salesman_id
            ?: $payment->invoice?->shop?->assigned_salesman_id
            ?: $payment->shop?->assigned_salesman_id;

        return $id ? (int) $id : null;
    }

    public function nextNumber(): string
    {
        $seq = Commission::withTrashed()->count() + 1;

        return 'COM-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{accrued:float, approved:float, paid:float, rejected:float, count_accrued:int, count_approved:int, count_paid:int}
     */
    public function pipelineTotals(): array
    {
        $rows = Commission::query()
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(commission_amount), 0) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $amount = fn (string $status) => (float) ($rows[$status]->total ?? 0);
        $count = fn (string $status) => (int) ($rows[$status]->cnt ?? 0);

        return [
            'accrued' => $amount(Commission::STATUS_ACCRUED),
            'approved' => $amount(Commission::STATUS_APPROVED),
            'paid' => $amount(Commission::STATUS_PAID),
            'rejected' => $amount(Commission::STATUS_REJECTED),
            'count_accrued' => $count(Commission::STATUS_ACCRUED),
            'count_approved' => $count(Commission::STATUS_APPROVED),
            'count_paid' => $count(Commission::STATUS_PAID),
        ];
    }
}
