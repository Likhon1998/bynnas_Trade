<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RewardService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function ensureTargetHitReward(SalesTarget $target, ?User $actor = null): ?Reward
    {
        if (! $target->target_met) {
            return null;
        }

        $existing = Reward::query()
            ->where('sales_target_id', $target->id)
            ->where('type', Reward::TYPE_TARGET_HIT)
            ->whereNotIn('status', [Reward::STATUS_CANCELLED])
            ->first();

        if ($existing) {
            return $existing;
        }

        // Flat celebration reward: 0.25% of achieved, min ৳500
        $amount = max(500, round((float) $target->achieved_amount * 0.0025, 2));

        $reward = Reward::query()->create([
            'number' => $this->nextNumber(),
            'salesman_id' => $target->salesman_id,
            'sales_target_id' => $target->id,
            'type' => Reward::TYPE_TARGET_HIT,
            'title' => 'Monthly target achieved · '.$target->periodLabel(),
            'year' => $target->year,
            'month' => $target->month,
            'amount' => $amount,
            'status' => Reward::STATUS_PENDING,
            'notes' => 'Auto-created when target was met',
            'created_by' => $actor?->id,
        ]);

        $this->auditLogger->log('rewards', 'created', "Reward {$reward->number} pending", $reward, null, ['amount' => $amount], $actor);

        return $reward;
    }

    public function createManual(array $data, ?User $actor = null): Reward
    {
        $reward = Reward::query()->create([
            'number' => $this->nextNumber(),
            'salesman_id' => $data['salesman_id'],
            'sales_target_id' => $data['sales_target_id'] ?? null,
            'type' => $data['type'] ?? Reward::TYPE_MANUAL,
            'title' => $data['title'],
            'year' => (int) $data['year'],
            'month' => (int) $data['month'],
            'amount' => (float) $data['amount'],
            'status' => Reward::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor?->id,
        ]);

        $this->auditLogger->log('rewards', 'created', "Reward {$reward->number} created", $reward, null, null, $actor);

        return $reward->fresh(['salesman']);
    }

    public function award(Reward $reward, ?User $actor = null): Reward
    {
        if ($reward->status !== Reward::STATUS_PENDING) {
            throw ValidationException::withMessages(['reward' => 'Only pending rewards can be awarded.']);
        }

        $reward->update([
            'status' => Reward::STATUS_AWARDED,
            'awarded_at' => now(),
            'awarded_by' => $actor?->id,
        ]);

        $this->auditLogger->log('rewards', 'awarded', "Reward {$reward->number} awarded", $reward, null, null, $actor);

        return $reward->fresh();
    }

    public function markPaid(Reward $reward, ?User $actor = null): Reward
    {
        if (! in_array($reward->status, [Reward::STATUS_AWARDED, Reward::STATUS_PENDING], true)) {
            throw ValidationException::withMessages(['reward' => 'Reward cannot be marked paid.']);
        }

        $reward->update([
            'status' => Reward::STATUS_PAID,
            'awarded_at' => $reward->awarded_at ?? now(),
            'awarded_by' => $reward->awarded_by ?? $actor?->id,
        ]);

        $this->auditLogger->log('rewards', 'paid', "Reward {$reward->number} paid", $reward, null, null, $actor);

        return $reward->fresh();
    }

    public function cancel(Reward $reward, string $reason, ?User $actor = null): Reward
    {
        if ($reward->status === Reward::STATUS_PAID) {
            throw ValidationException::withMessages(['reward' => 'Paid rewards cannot be cancelled.']);
        }

        $reward->update([
            'status' => Reward::STATUS_CANCELLED,
            'notes' => trim(($reward->notes ? $reward->notes."\n" : '').'Cancelled: '.$reason),
        ]);

        $this->auditLogger->log('rewards', 'cancelled', "Reward {$reward->number} cancelled", $reward, null, ['reason' => $reason], $actor);

        return $reward->fresh();
    }

    public function nextNumber(): string
    {
        $seq = Reward::withTrashed()->count() + 1;

        return 'RWD-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
