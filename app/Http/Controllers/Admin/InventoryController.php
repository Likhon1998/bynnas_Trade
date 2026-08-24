<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLedger;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('inventory.view'), 403);

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $warehouseId = $request->integer('warehouse_id') ?: Warehouse::defaultWarehouse()?->id;

        $stocks = WarehouseStock::query()
            ->with(['product.category', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('product_id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventory.index', compact('stocks', 'warehouses', 'warehouseId'));
    }

    public function receiveForm(Request $request)
    {
        abort_unless($request->user()->can('inventory.receive'), 403);

        return view('admin.inventory.receive', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('status', Product::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function receive(Request $request)
    {
        abort_unless($request->user()->can('inventory.receive'), 403);

        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        $this->inventory->receive(
            Warehouse::query()->findOrFail($data['warehouse_id']),
            Product::query()->findOrFail($data['product_id']),
            (int) $data['qty'],
            $request->user(),
            $data['notes'] ?? null,
            $data['reference'] ?? null,
        );

        return redirect()->route('inventory.index', ['warehouse_id' => $data['warehouse_id']])
            ->with('success', 'Stock received.');
    }

    public function ledger(Request $request)
    {
        abort_unless($request->user()->can('inventory.view'), 403);

        $entries = InventoryLedger::query()
            ->with(['warehouse', 'product', 'order', 'creator'])
            ->when($request->warehouse_id, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventory.ledger', [
            'entries' => $entries,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
        ]);
    }
}
