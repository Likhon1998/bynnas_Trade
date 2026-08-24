<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Shop;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(private ReturnService $returns) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('returns.view'), 403);

        $returns = ProductReturn::query()
            ->with(['shop', 'order'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('returns.create'), 403);

        return view('admin.returns.create', [
            'shops' => Shop::query()->whereIn('status', [Shop::STATUS_ACTIVE, Shop::STATUS_ON_HOLD])->orderBy('name')->get(),
            'orders' => Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_DISPATCHED])->latest()->limit(50)->get(),
            'products' => Product::query()->where('status', Product::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('returns.create'), 403);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'reason_type' => ['required', 'in:warranty,defect,wrong_item,other'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'restock' => ['nullable', 'boolean'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $return = $this->returns->create($data + [
            'restock' => $request->boolean('restock', true),
        ], $data['items'], $request->user());

        return redirect()->route('returns.show', $return)->with('success', 'Return submitted for review.');
    }

    public function show(Request $request, ProductReturn $productReturn)
    {
        abort_unless($request->user()->can('returns.view'), 403);

        $productReturn->load(['shop', 'order', 'invoice', 'items.product', 'approver', 'creator']);

        return view('admin.returns.show', ['return' => $productReturn]);
    }

    public function approve(Request $request, ProductReturn $productReturn)
    {
        abort_unless($request->user()->can('returns.approve'), 403);

        $data = $request->validate(['resolution_notes' => ['nullable', 'string', 'max:2000']]);
        $this->returns->approve($productReturn, $request->user(), $data['resolution_notes'] ?? null);

        return back()->with('success', 'Return approved — credit issued'.($productReturn->restock ? ' and stock restocked' : '').'.');
    }

    public function reject(Request $request, ProductReturn $productReturn)
    {
        abort_unless($request->user()->can('returns.reject'), 403);

        $data = $request->validate(['resolution_notes' => ['required', 'string', 'max:2000']]);
        $this->returns->reject($productReturn, $data['resolution_notes'], $request->user());

        return back()->with('success', 'Return rejected.');
    }
}
