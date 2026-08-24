<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Services\CommissionService;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(private CommissionService $commissions) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('commissions.view'), 403);

        $commissions = Commission::query()
            ->with(['salesman', 'payment', 'invoice'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->year, fn ($q, $year) => $q->where('year', $year))
            ->when($request->month, fn ($q, $month) => $q->where('month', $month))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.commissions.index', [
            'commissions' => $commissions,
            'rule' => CommissionRule::activeDefault(),
        ]);
    }

    public function updateRule(Request $request)
    {
        abort_unless($request->user()->can('commissions.manage'), 403);

        $data = $request->validate([
            'collection_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'target_bonus_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $rule = CommissionRule::activeDefault();
        if (! $rule) {
            $rule = CommissionRule::query()->create([
                'name' => 'Default salesman commission',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $rule->update($data);

        return back()->with('success', 'Commission rates updated.');
    }

    public function approve(Request $request, Commission $commission)
    {
        abort_unless($request->user()->can('commissions.approve'), 403);
        $this->commissions->approve($commission, $request->user());

        return back()->with('success', 'Commission approved.');
    }

    public function pay(Request $request, Commission $commission)
    {
        abort_unless($request->user()->can('commissions.approve'), 403);
        $this->commissions->markPaid($commission, $request->user());

        return back()->with('success', 'Commission marked paid.');
    }

    public function reject(Request $request, Commission $commission)
    {
        abort_unless($request->user()->can('commissions.approve'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->commissions->reject($commission, $data['reason'], $request->user());

        return back()->with('success', 'Commission rejected.');
    }
}
