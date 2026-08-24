<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\TargetService;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function __construct(private TargetService $targets) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('targets.view'), 403);

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $rows = SalesTarget::query()
            ->with(['salesman.salesmanProfile.territory', 'territory'])
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('salesman_id')
            ->get();

        return view('admin.targets.index', [
            'rows' => $rows,
            'year' => $year,
            'month' => $month,
            'salesmen' => User::query()
                ->where('portal', User::PORTAL_SALESMAN)
                ->where('is_active', true)
                ->with('salesmanProfile')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('targets.manage'), 403);

        $data = $request->validate([
            'salesman_id' => ['required', 'exists:users,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $this->targets->upsert($data, $request->user());

        return back()->with('success', 'Sales target saved and achievement recalculated.');
    }

    public function seedMonth(Request $request)
    {
        abort_unless($request->user()->can('targets.manage'), 403);

        $count = $this->targets->seedCurrentMonthFromProfiles($request->user());

        return back()->with('success', "Seeded / refreshed {$count} salesman target(s) for ".now()->format('Y-m').'.');
    }

    public function recalculate(Request $request, SalesTarget $target)
    {
        abort_unless($request->user()->can('targets.manage'), 403);

        $this->targets->recalculate($target);

        return back()->with('success', 'Target achievement recalculated.');
    }
}
