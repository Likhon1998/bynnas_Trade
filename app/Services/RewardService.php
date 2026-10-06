<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardService
{
    private const AUTO_WITHDRAWN = '[auto] Target no longer met';

    public function __construct(private AuditLogger $auditLogger) {}

    /** Cancel an unpaid target reward once returns or corrections push the salesman back under target. */
    public function withdrawTargetHitReward(SalesTarget $target, ?User $actor = null): ?Reward
    {
        if ($target->target_met) {
            return null;
        }

        return DB::transaction(function () use ($target, $actor) {
            $reward = Reward::query()
                ->where('sales_target_id', $target->id)
                ->where('type', Reward::TYPE_TARGET_HIT)
                ->whereIn('status', [Reward::STATUS_PENDING, Reward::STATUS_AWARDED])
                ->lockForUpdate()
                ->first();

            if (! $reward) {
                return null;
            }

            $reward->update([
                'status' => Reward::STATUS_CANCELLED,
                'notes' => trim(($reward->notes ? $reward->notes."\n" : '').self::AUTO_WITHDRAWN),
            ]);
            $this->auditLogger->log('rewards', 'cancelled', "Reward {$reward->number} withdrawn — target no longer met", $reward, null, null, $actor);

            return $reward;
        });
    }

    public function ensureTargetHitReward(SalesTarget $target, ?User $actor = null): ?Reward
    {
        if (! $target->target_met) {
            return null;
        }

        return DB::transaction(function () use ($target, $actor) {
            $target = SalesTarget::query()->lockForUpdate()->findOrFail($target->id);

            // Any earlier reward for this target counts, including a cancelled one — never re-issue.
            $existing = Reward::query()
                ->where('sales_target_id', $target->id)
                ->where('type', Reward::TYPE_TARGET_HIT)
                ->first();

            if ($existing) {
                if ($existing->status === Reward::STATUS_CANCELLED && str_contains((string) $existing->notes, self::AUTO_WITHDRAWN)) {
                    $existing->update([
                        'status' => Reward::STATUS_PENDING,
                        'amount' => max(500, round((float) $target->achieved_amount * 0.0025, 2)),
                        'notes' => trim(str_replace(self::AUTO_WITHDRAWN, '', (string) $existing->notes))."\nRestored: target met again",
                    ]);
                    $this->auditLogger->log('rewards', 'restored', "Reward {$existing->number} restored — target met again", $existing, null, null, $actor);
                }

                return $existing->fresh();
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
        });
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
        if ($reward->status !== Reward::STATUS_AWARDED) {
            throw ValidationException::withMessages(['reward' => 'Award the reward before marking it paid.']);
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
        return \App\Support\DocumentNumber::next(Reward::class, 'RWD-'.now()->format('ymd').'-', 4);
    }
}
