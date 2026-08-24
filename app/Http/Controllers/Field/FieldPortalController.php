<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SalesTarget;
use App\Models\Shop;
use App\Models\ShopVisit;
use App\Services\OrderService;
use App\Services\VisitService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FieldPortalController extends Controller
{
    public function __construct(
        private VisitService $visits,
        private OrderService $orders,
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();

        $openVisit = ShopVisit::query()
            ->with('shop')
            ->where('salesman_id', $user->id)
            ->whereNull('checked_out_at')
            ->first();

        $todayVisits = ShopVisit::query()
            ->where('salesman_id', $user->id)
            ->whereDate('checked_in_at', today())
            ->count();

        $pendingOrders = Order::query()
            ->where('salesman_id', $user->id)
            ->where('status', Order::STATUS_PENDING_AUDIT)
            ->count();

        $monthTotal = Order::query()
            ->where('salesman_id', $user->id)
            ->whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year)
            ->sum('total');

        $shops = $user->assignedShops()
            ->with('territory')
            ->where('status', Shop::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $recentOrders = Order::query()
            ->with('shop')
            ->where('salesman_id', $user->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $salesmanProfile = $user->salesmanProfile;
        $salesTarget = SalesTarget::query()
            ->where('salesman_id', $user->id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        return view('field.dashboard', compact(
            'openVisit', 'todayVisits', 'pendingOrders', 'monthTotal', 'shops', 'recentOrders',
            'salesmanProfile', 'salesTarget'
        ));
    }

    public function shops(Request $request)
    {
        $shops = $request->user()->assignedShops()
            ->with(['territory', 'priceGroup'])
            ->where('status', Shop::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('field.shops', compact('shops'));
    }

    public function checkIn(Request $request, Shop $shop)
    {
        $this->assertAssignedShop($request, $shop);

        $data = $request->validate([
            'purpose' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $visit = $this->visits->checkIn($request->user(), $shop, $data);

        return redirect()->route('field.visit.show', $visit)->with('success', 'Checked in at '.$shop->name);
    }

    public function showVisit(Request $request, ShopVisit $visit)
    {
        abort_unless($visit->salesman_id === $request->user()->id, 403);

        $visit->load(['shop.priceGroup', 'order']);

        $products = Product::query()
            ->with('prices')
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->orderBy('name')
            ->get();

        return view('field.visit', compact('visit', 'products'));
    }

    public function checkOut(Request $request, ShopVisit $visit)
    {
        abort_unless($visit->salesman_id === $request->user()->id, 403);

        $data = $request->validate([
            'outcome' => ['required', 'in:order_taken,no_order,closed,follow_up'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->visits->checkOut($visit, $data, $request->user());

        return redirect()->route('field.dashboard')->with('success', 'Visit closed.');
    }

    public function submitOrder(Request $request, ShopVisit $visit)
    {
        abort_unless($visit->salesman_id === $request->user()->id, 403);
        abort_unless($visit->isOpen(), 422);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $order = $this->orders->collectFromSalesman(
            $request->user(),
            $visit->shop,
            $data['items'],
            $visit,
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('field.orders.show', $order)
            ->with('success', 'Order '.$order->number.' submitted for Super Admin audit.');
    }

    public function orders(Request $request)
    {
        $orders = Order::query()
            ->with('shop')
            ->where('salesman_id', $request->user()->id)
            ->latest('submitted_at')
            ->paginate(15);

        return view('field.orders', compact('orders'));
    }

    public function showOrder(Request $request, Order $order)
    {
        abort_unless($order->salesman_id === $request->user()->id, 403);

        $order->load(['shop', 'items', 'visit']);

        return view('field.order-show', compact('order'));
    }

    private function assertAssignedShop(Request $request, Shop $shop): void
    {
        if ($shop->assigned_salesman_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'shop' => 'This shop is not assigned to you.',
            ]);
        }
    }
}
