<?php

namespace App\Services;

use App\Models\SalaryPayment;
use App\Models\SalesmanSalary;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function setMonthlySalary(User $salesman, float $amount, string $effectiveFrom, ?User $actor = null, ?string $notes = null): SalesmanSalary
    {
        return DB::transaction(function () use ($salesman, $amount, $effectiveFrom, $actor, $notes) {
            SalesmanSalary::query()
                ->where('user_id', $salesman->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $salary = SalesmanSalary::query()->create([
                'user_id' => $salesman->id,
                'monthly_salary' => $amount,
                'effective_from' => $effectiveFrom,
                'is_active' => true,
                'notes' => $notes,
                'created_by' => $actor?->id,
            ]);

            $this->auditLogger->log(
                'salaries',
                'set',
                "Monthly salary set for {$salesman->name}",
                $salary,
                null,
                ['amount' => $amount, 'effective_from' => $effectiveFrom],
                $actor,
            );

            return $salary;
        });
    }

    public function currentSalary(User $salesman): ?SalesmanSalary
    {
        return SalesmanSalary::query()
            ->where('user_id', $salesman->id)
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->first();
    }

    public function payMonth(
        User $salesman,
        int $year,
        int $month,
        float $amount,
        float $bonus = 0,
        ?string $method = null,
        ?string $notes = null,
        ?User $actor = null,
        bool $markPaid = true,
    ): SalaryPayment {
        return DB::transaction(function () use ($salesman, $year, $month, $amount, $bonus, $method, $notes, $actor, $markPaid) {
            $existing = SalaryPayment::query()
                ->where('user_id', $salesman->id)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === SalaryPayment::STATUS_PAID) {
                throw ValidationException::withMessages([
                    'month' => 'Salary for this month is already paid.',
                ]);
            }

            $payload = [
                'amount' => $amount,
                'bonus' => $bonus,
                'method' => $method,
                'notes' => $notes,
                'status' => $markPaid ? SalaryPayment::STATUS_PAID : SalaryPayment::STATUS_PENDING,
                'paid_at' => $markPaid ? now() : null,
                'paid_by' => $markPaid ? $actor?->id : null,
            ];

            if ($existing) {
                $existing->update($payload);
                $payment = $existing;
            } else {
                $payment = SalaryPayment::query()->create(array_merge($payload, [
                    'user_id' => $salesman->id,
                    'year' => $year,
                    'month' => $month,
                ]));
            }

            $this->auditLogger->log(
                'salaries',
                $markPaid ? 'paid' : 'recorded',
                "Salary {$payment->periodLabel()} for {$salesman->name}",
                $payment,
                null,
                ['amount' => $amount, 'bonus' => $bonus],
                $actor,
            );

            return $payment->fresh(['user', 'payer']);
        });
    }

    /**
     * @return array{current: ?SalesmanSalary, payments: \Illuminate\Support\Collection, outstanding_months: int}
     */
    public function summaryFor(User $salesman): array
    {
        $current = $this->currentSalary($salesman);
        $payments = SalaryPayment::query()
            ->where('user_id', $salesman->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(24)
            ->get();

        $paidKeys = $payments->where('status', SalaryPayment::STATUS_PAID)
            ->map(fn (SalaryPayment $p) => $p->year.'-'.$p->month)
            ->all();

        $outstanding = 0;
        if ($current) {
            $cursor = now()->startOfMonth();
            $start = $current->effective_from->copy()->startOfMonth();
            while ($start->lte($cursor)) {
                $key = $start->year.'-'.$start->month;
                if (! in_array($key, $paidKeys, true)) {
                    $outstanding++;
                }
                $start->addMonth();
            }
        }

        return [
            'current' => $current,
            'payments' => $payments,
            'outstanding_months' => $outstanding,
        ];
    }

    public function monthPaidTotal(int $year, int $month): float
    {
        return (float) SalaryPayment::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', SalaryPayment::STATUS_PAID)
            ->selectRaw('COALESCE(SUM(amount + bonus), 0) as total')
            ->value('total');
    }
}
