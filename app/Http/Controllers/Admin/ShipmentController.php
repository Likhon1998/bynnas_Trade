<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentService $shipments) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('shipments.view'), 403);

        $shipments = Shipment::query()
            ->with(['supplier', 'warehouse'])
            ->withSum('items as items_qty', 'quantity')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhere('tracking_ref', 'like', "%{$search}%")
                        ->orWhere('container_no', 'like', "%{$search}%")
                        ->orWhere('origin', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.shipments.index', compact('shipments'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('shipments.create'), 403);

        return view('admin.shipments.create', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'purchases' => Purchase::query()->whereIn('status', [Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL])->latest()->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('status', Product::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('shipments.create'), 403);

        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'origin' => ['nullable', 'string', 'max:120'],
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_ref' => ['nullable', 'string', 'max:120'],
            'container_no' => ['nullable', 'string', 'max:80'],
            'shipped_at' => ['nullable', 'date'],
            'eta_at' => ['nullable', 'date'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'customs_duty' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_cost_bdt' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($data['purchase_id'])) {
            $purchase = Purchase::query()->findOrFail($data['purchase_id']);
            $shipment = $this->shipments->createFromPurchase($purchase, $data, $request->user());
        } else {
            $shipment = $this->shipments->create($data, $data['items'] ?? [], $request->user());
        }

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment created.');
    }

    public function show(Request $request, Shipment $shipment)
    {
        abort_unless($request->user()->can('shipments.view'), 403);

        $shipment->load(['supplier', 'purchase', 'warehouse', 'items.product', 'creator', 'receiver']);
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.shipments.show', compact('shipment', 'warehouses'));
    }

    public function updateCosts(Request $request, Shipment $shipment)
    {
        abort_unless($request->user()->can('shipments.edit'), 403);

        $data = $request->validate([
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'customs_duty' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->shipments->updateCosts($shipment, $data, $request->user());

        return back()->with('success', 'Costs updated and landed cost re-allocated.');
    }

    public function markArrived(Request $request, Shipment $shipment)
    {
        abort_unless($request->user()->can('shipments.edit'), 403);
        $this->shipments->markArrived($shipment, $request->user());

        return back()->with('success', 'Shipment marked arrived.');
    }

    public function receive(Request $request, Shipment $shipment)
    {
        abort_unless($request->user()->can('shipments.receive'), 403);

        $data = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $this->shipments->receive($shipment, $request->user(), $data['warehouse_id'] ?? null);

        return back()->with('success', 'Shipment received into warehouse. Stock and landed costs updated.');
    }
}
