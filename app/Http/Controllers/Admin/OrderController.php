<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $pendingCount = Order::query()->where('status', Order::STATUS_PENDING_AUDIT)->count();

        $orders = Order::query()
            ->with(['shop', 'salesman', 'auditor'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhereHas('shop', fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->source, fn ($q, $source) => $q->where('source', $source))
            ->when($request->boolean('audit_queue'), fn ($q) => $q->where('status', Order::STATUS_PENDING_AUDIT))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'pendingCount'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Order::class);

        return view('admin.orders.create', [
            'shops' => Shop::query()->where('status', Shop::STATUS_ACTIVE)->orderBy('name')->get(),
            'products' => Product::query()->with('prices')->where('status', Product::STATUS_ACTIVE)->where('is_published', true)->orderBy('name')->get(),
            'salesmen' => User::query()->where('portal', User::PORTAL_SALESMAN)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'salesman_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $shop = Shop::query()->findOrFail($data['shop_id']);
        $order = $this->orders->createFromAdmin(
            $request->user(),
            $shop,
            $data['items'],
            $data['notes'] ?? null,
            $data['salesman_id'] ?? null,
        );

        return redirect()->route('orders.show', $order)->with('success', 'Order submitted for Super Admin audit.');
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'shop.priceGroup', 'salesman', 'visit', 'items.product',
            'creator', 'auditor', 'canceller', 'statusHistories.user',
            'warehouse', 'fulfilment.delivery', 'invoice',
        ]);

        $snapshot = $this->orders->auditSnapshot($order);

        return view('admin.orders.show', compact('order', 'snapshot'));
    }

    public function approve(Request $request, Order $order)
    {
        $this->authorize('approve', $order);

        $data = $request->validate([
            'audit_notes' => ['nullable', 'string', 'max:2000'],
            'credit_override' => ['nullable', 'boolean'],
        ]);

        $this->orders->approve(
            $order,
            $request->user(),
            $data['audit_notes'] ?? null,
            $request->boolean('credit_override'),
        );

        return redirect()->route('orders.show', $order)->with('success', 'Order approved and stock reserved.');
    }

    public function reject(Request $request, Order $order)
    {
        $this->authorize('reject', $order);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->orders->reject($order, $request->user(), $data['rejection_reason']);

        return redirect()->route('orders.show', $order)->with('success', 'Order rejected.');
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ]);

        $wasReserved = $order->stock_reserved || $order->isApproved();
        $this->orders->cancel($order, $request->user(), $data['cancellation_reason']);

        $message = $wasReserved
            ? 'Order cancelled and reserved stock released.'
            : 'Order cancelled.';

        return redirect()->route('orders.show', $order)->with('success', $message);
    }
}
