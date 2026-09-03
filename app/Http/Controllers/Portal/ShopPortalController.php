<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Services\OrderService;
use Illuminate\Http\Request;

class ShopPortalController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function dashboard(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $productCount = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->count();

        $pendingOrders = Order::query()
            ->where('shop_id', $shop->id)
            ->where('status', Order::STATUS_PENDING_AUDIT)
            ->count();

        $recentOrders = Order::query()
            ->where('shop_id', $shop->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view('portal.dashboard', [
            'shop' => $shop,
            'productCount' => $productCount,
            'pendingOrders' => $pendingOrders,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function products(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        $shop->load('priceGroup');

        $products = Product::query()
            ->with(['category', 'brand', 'prices'])
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('portal.products.index', [
            'shop' => $shop,
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function createOrder(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        $shop->load('priceGroup');

        $products = Product::query()
            ->with('prices')
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->orderBy('name')
            ->get();

        return view('portal.orders.create', compact('shop', 'products'));
    }

    public function storeOrder(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $order = $this->orders->placeFromShopPortal(
            $request->user(),
            $shop,
            $data['items'],
            $data['notes'] ?? null,
        );

        return redirect()->route('portal.orders.show', $order)
            ->with('success', 'Order '.$order->number.' submitted for Super Admin audit.');
    }

    public function editOrder(Request $request, Order $order)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        abort_unless($order->shop_id === $shop->id, 404);
        abort_unless($order->canPartnerEdit(), 403, 'This order can no longer be edited.');

        $shop->load('priceGroup');
        $order->load('items');

        $products = Product::query()
            ->with('prices')
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->orderBy('name')
            ->get();

        $quantities = $order->items->mapWithKeys(
            fn ($item) => [(int) $item->product_id => (int) $item->quantity]
        )->all();

        return view('portal.orders.edit', compact('shop', 'products', 'order', 'quantities'));
    }

    public function updateOrder(Request $request, Order $order)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        abort_unless($order->shop_id === $shop->id, 404);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $order = $this->orders->updatePendingFromShopPortal(
            $request->user(),
            $shop,
            $order,
            $data['items'],
            $data['notes'] ?? null,
        );

        return redirect()->route('portal.orders.show', $order)
            ->with('success', 'Order '.$order->number.' updated. Still waiting for Super Admin audit.');
    }

    public function destroyOrder(Request $request, Order $order)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        abort_unless($order->shop_id === $shop->id, 404);
        abort_unless($order->canDeleteBeforeApproval(), 403, 'This order can no longer be deleted.');

        $number = $order->number;
        $this->orders->deleteBeforeApproval($order, $request->user());

        return redirect()->route('portal.orders')
            ->with('success', 'Order '.$number.' deleted before approval.');
    }

    public function orders(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $orders = Order::query()
            ->where('shop_id', $shop->id)
            ->latest('submitted_at')
            ->paginate(15);

        return view('portal.orders.index', compact('shop', 'orders'));
    }

    public function showOrder(Request $request, Order $order)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        abort_unless($order->shop_id === $shop->id, 404);

        $order->load(['items', 'salesman', 'auditor']);

        return view('portal.orders.show', compact('shop', 'order'));
    }

    public function profile(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');
        $shop->load(['territory', 'priceGroup', 'assignedSalesman']);

        return view('portal.profile', compact('shop'));
    }
}
