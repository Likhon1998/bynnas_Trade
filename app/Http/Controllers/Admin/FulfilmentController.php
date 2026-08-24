<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\OrderFulfilment;
use App\Models\User;
use App\Services\FulfilmentService;
use Illuminate\Http\Request;

class FulfilmentController extends Controller
{
    public function __construct(private FulfilmentService $fulfilment) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('fulfilment.view'), 403);

        $queue = OrderFulfilment::query()
            ->with(['order.shop', 'warehouse', 'picker'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when(! $request->status, fn ($q) => $q->whereNotIn('status', [OrderFulfilment::STATUS_DELIVERED]))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.fulfilment.index', compact('queue'));
    }

    public function show(Request $request, OrderFulfilment $fulfilment)
    {
        abort_unless($request->user()->can('fulfilment.view'), 403);

        $fulfilment->load(['order.items.product', 'order.shop', 'warehouse', 'picker', 'packer', 'dispatcher', 'delivery.assignee']);

        $drivers = User::query()
            ->where('portal', User::PORTAL_ADMIN)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Delivery Staff', 'Delivery Manager', 'Warehouse Staff', 'Warehouse Manager', 'Super Admin', 'Admin']))
            ->orderBy('name')
            ->get();

        return view('admin.fulfilment.show', compact('fulfilment', 'drivers'));
    }

    public function startPick(Request $request, OrderFulfilment $fulfilment)
    {
        abort_unless($request->user()->can('fulfilment.pick'), 403);
        $this->fulfilment->startPick($fulfilment, $request->user());

        return back()->with('success', 'Picking started.');
    }

    public function completePick(Request $request, OrderFulfilment $fulfilment)
    {
        abort_unless($request->user()->can('fulfilment.pick'), 403);

        $data = $request->validate(['warehouse_notes' => ['nullable', 'string', 'max:1000']]);
        $this->fulfilment->completePick($fulfilment, $request->user(), $data['warehouse_notes'] ?? null);

        return back()->with('success', 'Pick completed — stock deducted from warehouse.');
    }

    public function pack(Request $request, OrderFulfilment $fulfilment)
    {
        abort_unless($request->user()->can('fulfilment.pack'), 403);

        $data = $request->validate(['warehouse_notes' => ['nullable', 'string', 'max:1000']]);
        $this->fulfilment->pack($fulfilment, $request->user(), $data['warehouse_notes'] ?? null);

        return back()->with('success', 'Order packed.');
    }

    public function dispatch(Request $request, OrderFulfilment $fulfilment)
    {
        abort_unless($request->user()->can('fulfilment.dispatch'), 403);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'tracking_ref' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $delivery = $this->fulfilment->dispatch($fulfilment, $request->user(), $data);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Dispatched as '.$delivery->number);
    }
}
