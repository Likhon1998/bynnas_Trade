<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\SalaryService;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function __construct(private SalaryService $salaries) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('salaries.view'), 403);

        $salesmen = User::query()
            ->where('portal', User::PORTAL_SALESMAN)
            ->with('salesmanProfile')
            ->orderBy('name')
            ->get();

        $rows = $salesmen->map(function (User $salesman) {
            $summary = $this->salaries->summaryFor($salesman);

            return [
                'salesman' => $salesman,
                'current' => $summary['current'],
                'outstanding_months' => $summary['outstanding_months'],
                'last_payment' => $summary['payments']->first(),
            ];
        });

        $payments = SalaryPayment::query()
            ->with(['user', 'payer'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(30);

        return view('admin.salaries.index', [
            'rows' => $rows,
            'payments' => $payments,
            'monthPaid' => $this->salaries->monthPaidTotal(now()->year, now()->month),
        ]);
    }

    public function storeSalary(Request $request)
    {
        abort_unless($request->user()->can('salaries.manage'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $salesman = User::query()->findOrFail($data['user_id']);
        abort_unless($salesman->portal === User::PORTAL_SALESMAN, 404);

        $this->salaries->setMonthlySalary(
            $salesman,
            (float) $data['monthly_salary'],
            $data['effective_from'],
            $request->user(),
            $data['notes'] ?? null,
        );

        return back()->with('success', 'Monthly salary saved.');
    }

    public function pay(Request $request)
    {
        abort_unless($request->user()->can('salaries.manage'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ]);

        $salesman = User::query()->findOrFail($data['user_id']);
        abort_unless($salesman->portal === User::PORTAL_SALESMAN, 404);

        $this->salaries->payMonth(
            $salesman,
            (int) $data['year'],
            (int) $data['month'],
            (float) $data['amount'],
            (float) ($data['bonus'] ?? 0),
            $data['method'] ?? null,
            $data['notes'] ?? null,
            $request->user(),
            true,
        );

        return back()->with('success', 'Salary payment recorded.');
    }
}
