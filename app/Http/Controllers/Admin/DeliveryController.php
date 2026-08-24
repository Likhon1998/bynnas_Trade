<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\FulfilmentService;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(private FulfilmentService $fulfilment) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('deliveries.view'), 403);

        $deliveries = Delivery::query()
            ->with(['order', 'shop', 'warehouse', 'assignee'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('dispatched_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.deliveries.index', compact('deliveries'));
    }

    public function show(Request $request, Delivery $delivery)
    {
        abort_unless($request->user()->can('deliveries.view'), 403);

        $delivery->load(['order.items', 'shop', 'warehouse', 'assignee', 'fulfilment']);

        return view('admin.deliveries.show', compact('delivery'));
    }

    public function markDelivered(Request $request, Delivery $delivery)
    {
        abort_unless($request->user()->can('deliveries.update_status'), 403);

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $this->fulfilment->markDelivered($delivery, $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Marked as delivered.');
    }
}
