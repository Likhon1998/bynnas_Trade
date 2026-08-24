<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function __construct(private RewardService $rewards) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('rewards.view'), 403);

        $rewards = Reward::query()
            ->with(['salesman', 'salesTarget'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.rewards.index', [
            'rewards' => $rewards,
            'salesmen' => User::query()
                ->where('portal', User::PORTAL_SALESMAN)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('rewards.manage'), 403);

        $data = $request->validate([
            'salesman_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:manual,top_performer,target_hit'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->rewards->createManual($data, $request->user());

        return back()->with('success', 'Reward created.');
    }

    public function award(Request $request, Reward $reward)
    {
        abort_unless($request->user()->can('rewards.approve'), 403);
        $this->rewards->award($reward, $request->user());

        return back()->with('success', 'Reward awarded.');
    }

    public function pay(Request $request, Reward $reward)
    {
        abort_unless($request->user()->can('rewards.approve'), 403);
        $this->rewards->markPaid($reward, $request->user());

        return back()->with('success', 'Reward marked paid.');
    }

    public function cancel(Request $request, Reward $reward)
    {
        abort_unless($request->user()->can('rewards.manage'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->rewards->cancel($reward, $data['reason'], $request->user());

        return back()->with('success', 'Reward cancelled.');
    }
}
