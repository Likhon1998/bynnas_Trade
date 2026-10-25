<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLedger;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('inventory.view'), 403);

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $warehouseId = $request->integer('warehouse_id') ?: Warehouse::defaultWarehouse()?->id;
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;

        $scope = WarehouseStock::query()
            ->with(['product.category'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId));

        $scoped = (clone $scope)->get();
        $summary = $this->summarizeStock($scoped);
        $categoryStats = $this->categoryStats($scoped);

        $stocks = WarehouseStock::query()
            ->with(['product.category', 'product.images', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($request->filled('category_id'), function ($q) use ($categoryId) {
                if ((int) $categoryId === 0) {
                    $q->whereHas('product', fn ($p) => $p->whereNull('category_id'));
                } else {
                    $q->whereHas('product', fn ($p) => $p->where('category_id', $categoryId));
                }
            })
            ->when($request->search, function ($q, $search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('product_id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventory.index', compact(
            'stocks',
            'warehouses',
            'warehouseId',
            'categoryId',
            'summary',
            'categoryStats',
        ));
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

    /**
     * @param  Collection<int, WarehouseStock>  $stocks
     * @return array{value: float, products: int, units: int}
     */
    private function summarizeStock(Collection $stocks): array
    {
        return [
            'value' => round($stocks->sum(function (WarehouseStock $stock) {
                return (int) $stock->qty_on_hand * (float) ($stock->product?->inventoryUnitCost() ?? 0);
            }), 2),
            'products' => $stocks->pluck('product_id')->filter()->unique()->count(),
            'units' => (int) $stocks->sum('qty_on_hand'),
        ];
    }

    /**
     * @param  Collection<int, WarehouseStock>  $stocks
     * @return list<array{id: int|null, name: string, products: int, units: int, value: float}>
     */
    private function categoryStats(Collection $stocks): array
    {
        return $stocks
            ->groupBy(fn (WarehouseStock $stock) => $stock->product?->category_id ?: 0)
            ->map(function (Collection $rows) {
                $category = $rows->first()?->product?->category;

                return [
                    'id' => $category?->id,
                    'name' => $category?->name ?: 'Uncategorized',
                    'products' => $rows->pluck('product_id')->filter()->unique()->count(),
                    'units' => (int) $rows->sum('qty_on_hand'),
                    'value' => round($rows->sum(function (WarehouseStock $stock) {
                        return (int) $stock->qty_on_hand * (float) ($stock->product?->inventoryUnitCost() ?? 0);
                    }), 2),
                ];
            })
            ->sortByDesc('products')
            ->values()
            ->all();
    }
}
