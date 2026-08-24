<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService $purchases,
        private ShipmentService $shipments,
    ) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('purchases.view'), 403);

        $purchases = Purchase::query()
            ->with('supplier')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.purchases.index', compact('purchases'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('purchases.create'), 403);

        return view('admin.purchases.create', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('status', Product::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('purchases.create'), 403);

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0.0001'],
            'ordered_at' => ['nullable', 'date'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_cost_foreign' => ['nullable', 'numeric', 'min:0'],
        ]);

        $purchase = $this->purchases->create($data, $data['items'], $request->user());

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase order created.');
    }

    public function show(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->can('purchases.view'), 403);

        $purchase->load(['supplier', 'items.product', 'shipments', 'creator']);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function createShipment(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->can('shipments.create'), 403);

        $data = $request->validate([
            'origin' => ['nullable', 'string', 'max:120'],
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_ref' => ['nullable', 'string', 'max:120'],
            'container_no' => ['nullable', 'string', 'max:80'],
            'eta_at' => ['nullable', 'date'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'customs_duty' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $shipment = $this->shipments->createFromPurchase($purchase, $data, $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'China shipment created from PO.');
    }
}
