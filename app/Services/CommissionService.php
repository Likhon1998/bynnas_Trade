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

        if (Commission::query()->where('payment_id', $payment->id)->where('type', Commission::TYPE_COLLECTION)->exists()) {
            return Commission::query()->where('payment_id', $payment->id)->where('type', Commission::TYPE_COLLECTION)->first();
        }

        $payment->loadMissing(['invoice.order', 'invoice.shop']);
        $order = $payment->invoice?->order;
        $salesmanId = $order?->salesman_id ?: $payment->invoice?->shop?->assigned_salesman_id;

        if (! $salesmanId) {
            return null;
        }

        $rule = CommissionRule::activeDefault();
        $rate = (float) ($rule?->collection_rate_percent ?? 3.5);
        $amount = round(((float) $payment->amount) * $rate / 100, 2);
        $when = $payment->verified_at ?? now();
        $year = (int) $when->year;
        $month = (int) $when->month;

        $salesman = User::query()->findOrFail($salesmanId);
        $target = $this->targets->ensureForSalesman($salesman, $year, $month, $actor);

        return DB::transaction(function () use ($payment, $order, $salesmanId, $target, $rate, $amount, $year, $month, $actor) {
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
                ['amount' => $amount, 'payment_id' => $payment->id],
                $actor,
            );

            return $commission->fresh(['salesman', 'payment']);
        });
    }

    public function maybeAccrueTargetBonus(SalesTarget $target, ?User $actor = null): ?Commission
    {
        if (! $target->target_met) {
            return null;
        }

        $existing = Commission::query()
            ->where('sales_target_id', $target->id)
            ->where('type', Commission::TYPE_TARGET_BONUS)
            ->whereNotIn('status', [Commission::STATUS_REJECTED])
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
        if (! in_array($commission->status, [Commission::STATUS_APPROVED, Commission::STATUS_ACCRUED], true)) {
            throw ValidationException::withMessages(['commission' => 'Commission cannot be marked paid.']);
        }

        $commission->update([
            'status' => Commission::STATUS_PAID,
            'paid_at' => now(),
            'approved_at' => $commission->approved_at ?? now(),
            'approved_by' => $commission->approved_by ?? $actor?->id,
        ]);

        $this->auditLogger->log('commissions', 'paid', "Commission {$commission->number} paid", $commission, null, null, $actor);

        return $commission->fresh();
    }

    public function reject(Commission $commission, string $reason, ?User $actor = null): Commission
    {
        if ($commission->status === Commission::STATUS_PAID) {
            throw ValidationException::withMessages(['commission' => 'Paid commissions cannot be rejected.']);
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

    public function nextNumber(): string
    {
        $seq = Commission::withTrashed()->count() + 1;

        return 'COM-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
