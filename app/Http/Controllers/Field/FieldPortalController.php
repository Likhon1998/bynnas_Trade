<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\PartnerInquiry;
use App\Models\Product;
use App\Models\Reward;
use App\Models\SalesTarget;
use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use App\Rules\BangladeshPhone;
use App\Services\AppNotificationService;
use App\Services\OrderService;
use App\Services\PartnerInquiryService;
use App\Services\ShopService;
use App\Services\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FieldPortalController extends Controller
{
    private const COUNTED_STATUSES_EXCLUDED = [Order::STATUS_REJECTED, Order::STATUS_CANCELLED, Order::STATUS_DRAFT];

    public const SHOP_CATEGORIES = ['Mobile phones', 'Accessories', 'Electronics', 'Computers', 'Home appliances', 'Grocery', 'Pharmacy', 'Other'];

    public const SHOP_INTEREST = ['hot' => 'Ready to order', 'warm' => 'Interested', 'cold' => 'Just looking'];

    public function __construct(
        private VisitService $visits,
        private OrderService $orders,
        private ShopService $shops,
        private AppNotificationService $notifications,
        private PartnerInquiryService $partnerInquiries,
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();

        $monthOrders = Order::query()
            ->where('salesman_id', $user->id)
            ->whereNotIn('status', self::COUNTED_STATUSES_EXCLUDED)
            ->whereBetween('submitted_at', [now()->startOfMonth(), now()->endOfMonth()]);

        $monthTotal = (float) (clone $monthOrders)->sum('total');
        $todayOrders = (clone $monthOrders)->whereDate('submitted_at', today());

        $salesTarget = SalesTarget::query()
            ->where('salesman_id', $user->id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();
        $target = (float) ($salesTarget?->target_amount ?? $user->salesmanProfile?->monthly_target ?? 0);

        $shops = $this->shopsWithVisits($user);
        $openVisit = $this->openVisit($user);

        $weekTotals = Order::query()
            ->where('salesman_id', $user->id)
            ->whereNotIn('status', self::COUNTED_STATUSES_EXCLUDED)
            ->where('submitted_at', '>=', today()->subDays(6))
            ->selectRaw('DATE(submitted_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
        $trend = collect(range(6, 0))->map(function (int $ago) use ($weekTotals) {
            $day = today()->subDays($ago);

            return ['label' => $ago === 0 ? 'Today' : $day->format('D'), 'total' => (float) ($weekTotals[$day->toDateString()] ?? 0)];
        });

        return view('field.dashboard', [
            'trend' => $trend,
            'routeShops' => $shops->sortBy(fn (Shop $s) => [$openVisit?->shop_id === $s->id ? 0 : 1, $s->visited_today ? 1 : 0, $s->name])->values(),
            'monthTotal' => $monthTotal,
            'target' => $target,
            'progress' => $target > 0 ? min(100, round($monthTotal / $target * 100)) : null,
            'daysLeft' => (int) now()->diffInDays(now()->endOfMonth()) + 1,
            'todayVisits' => ShopVisit::query()->where('salesman_id', $user->id)->whereDate('checked_in_at', today())->count(),
            'todayOrderCount' => (clone $todayOrders)->count(),
            'todayOrderTotal' => (float) (clone $todayOrders)->sum('total'),
            'waitingApproval' => Order::query()->where('salesman_id', $user->id)
                ->whereIn('status', [Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE])->count(),
            'monthCommission' => (float) Commission::query()->where('salesman_id', $user->id)
                ->where('year', now()->year)->where('month', now()->month)
                ->where('status', '!=', Commission::STATUS_REJECTED)->sum('commission_amount'),
            'openVisit' => $openVisit,
            'toVisit' => $shops->reject(fn ($s) => $s->visited_today)->take(5),
            'shopCount' => $shops->count(),
            'visitedCount' => $shops->where('visited_today', true)->count(),
            'recentOrders' => Order::query()->with('shop')->where('salesman_id', $user->id)
                ->latest('submitted_at')->limit(6)->get(),
            'shopsAddedMonth' => Shop::query()->where('created_by', $user->id)->where('created_at', '>=', now()->startOfMonth())->count(),
            'shopsPending' => Shop::query()->where('created_by', $user->id)->where('status', Shop::STATUS_PENDING)->count(),
            'loginsToCreate' => $this->loginsToCreate($user),
        ]);
    }

    public function shops(Request $request)
    {
        $user = $request->user();

        return view('field.shops', [
            'shops' => $this->shopsWithVisits($user),
            'openVisit' => $this->openVisit($user),
            'addedShops' => Shop::query()->withCount('users')->where('created_by', $user->id)->latest()->limit(50)->get(),
        ]);
    }

    public function createShop(Request $request)
    {
        return view('field.shop-create', [
            'openVisit' => $this->openVisit($request->user()),
            'categories' => self::SHOP_CATEGORIES,
            'interests' => self::SHOP_INTEREST,
        ]);
    }

    public function storeShop(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', new BangladeshPhone],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sells' => ['nullable', 'array', 'max:8'],
            'sells.*' => ['string', Rule::in(self::SHOP_CATEGORIES)],
            'interest' => ['nullable', Rule::in(array_keys(self::SHOP_INTEREST))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (! empty($data['phone'])) {
            $data['phone'] = BangladeshPhone::normalize($data['phone']);
            $existing = Shop::query()->where('phone', $data['phone'])->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'phone' => "This phone number already belongs to {$existing->name} ({$existing->code}).",
                ]);
            }
        }

        $data['name'] = trim((string) ($data['name'] ?? '')) ?: 'New shop '.$this->shops->nextCode();
        $data['owner_name'] = trim((string) ($data['owner_name'] ?? ''));
        $data['notes'] = collect([
            ! empty($data['sells']) ? 'Sells: '.implode(', ', $data['sells']) : null,
            ! empty($data['interest']) ? 'Interest: '.self::SHOP_INTEREST[$data['interest']] : null,
            $data['notes'] ?? null,
        ])->filter()->implode("\n") ?: null;
        unset($data['sells'], $data['interest']);

        $shop = $this->shops->create($data + [
            'status' => Shop::STATUS_PENDING,
            'assigned_salesman_id' => $user->id,
            'territory_id' => $user->salesmanProfile?->territory_id,
        ], $user);

        try {
            $this->notifications->shopAddedFromField($shop, $user);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->boolean('check_in') && ! $this->openVisit($user)) {
            $visit = $this->visits->checkIn($user, $shop, [
                'purpose' => 'New shop',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            return redirect()->route('field.visit.show', $visit)
                ->with('success', $shop->name.' added and visit started. The office will approve it before orders can be taken.');
        }

        return redirect()->route('field.shops')
            ->with('success', $shop->name.' added and sent to the office for approval.');
    }

    public function partners(Request $request)
    {
        $applications = PartnerInquiry::query()
            ->with('shop')
            ->where('submitted_by', $request->user()->id)
            ->latest()
            ->paginate(15);

        $byStatus = PartnerInquiry::query()->where('submitted_by', $request->user()->id)
            ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('field.partners', [
            'applications' => $applications,
            'loginsToCreate' => $this->loginsToCreate($request->user()),
            'counts' => [
                'all' => (int) $byStatus->sum(),
                'pending' => (int) (($byStatus[PartnerInquiry::STATUS_NEW] ?? 0) + ($byStatus[PartnerInquiry::STATUS_CONTACTED] ?? 0)),
                'accepted' => (int) ($byStatus[PartnerInquiry::STATUS_CONVERTED] ?? 0),
                'rejected' => (int) ($byStatus[PartnerInquiry::STATUS_CLOSED] ?? 0),
            ],
        ]);
    }

    public function partnerLogin(Request $request, PartnerInquiry $inquiry)
    {
        abort_unless($inquiry->submitted_by === $request->user()->id, 403);

        if (! $inquiry->isAccepted() || ! $inquiry->shop) {
            return redirect()->route('field.partners')->with('error', 'The office has not accepted '.$inquiry->business_name.' yet.');
        }

        return view('field.partner-login', [
            'inquiry' => $inquiry->load('shop'),
            'result' => $request->session()->get('login_result'),
        ]);
    }

    public function storePartnerLogin(Request $request, PartnerInquiry $inquiry)
    {
        abort_unless($inquiry->submitted_by === $request->user()->id, 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
        ], [
            'password.min' => 'Use at least 8 characters so the account is safe.',
        ]);

        try {
            $login = $this->partnerInquiries->setLogin($inquiry, $data['email'], $data['password'], $request->user());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        return redirect()->route('field.partners.login', $inquiry)->with('login_result', [
            'ok' => $login['ok'],
            'checks' => $login['checks'],
            'email' => $login['user']->email,
            'password' => $data['password'],
            'chat_url' => $login['whatsapp']['chat_url'] ?? null,
        ]);
    }

    public function createPartner(Request $request)
    {
        $shops = $this->partnerCandidateShops($request->user());

        return view('field.partner-create', [
            'shops' => $shops,
            'selectedShop' => $shops->firstWhere('id', (int) $request->query('shop')),
            'businessTypes' => PartnerInquiry::BUSINESS_TYPES,
        ]);
    }

    public function storePartner(Request $request)
    {
        $user = $request->user();
        $shopIds = $this->partnerCandidateShops($user)->modelKeys();

        $data = $request->validate([
            'shop_id' => ['nullable', 'integer', Rule::in($shopIds)],
            'business_name' => ['required', 'string', 'max:180'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:20', new BangladeshPhone(required: true)],
            'city' => ['nullable', 'string', 'max:80'],
            'business_type' => ['nullable', Rule::in(array_keys(PartnerInquiry::BUSINESS_TYPES))],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'shop_id.in' => 'Pick one of your own shops, or leave it empty for a new business.',
        ]);
        $data['phone'] = BangladeshPhone::normalize($data['phone']);
        $data['email'] = strtolower(trim($data['email']));

        if (User::query()->where('email', $data['email'])->where('portal', '!=', User::PORTAL_SHOP)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email belongs to a staff or salesman account. Use the shop owner\'s own email.']);
        }

        $duplicate = PartnerInquiry::query()
            ->whereIn('status', [PartnerInquiry::STATUS_NEW, PartnerInquiry::STATUS_CONTACTED])
            ->where(function ($q) use ($data) {
                $q->where('email', $data['email'])->orWhere('phone', $data['phone']);
                if (! empty($data['shop_id'])) {
                    $q->orWhere('shop_id', $data['shop_id']);
                }
            })
            ->first();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'email' => "{$duplicate->business_name} already has an application waiting (sent {$duplicate->created_at->format('d M')}).",
            ]);
        }

        $inquiry = $this->partnerInquiries->submitFromField($data, $user);

        try {
            $this->notifications->partnerFromField($inquiry, $user);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('field.partners')
            ->with('success', 'Partner application for '.$inquiry->business_name.' sent. The office will review it and send the owner their login.');
    }

    public function checkIn(Request $request, Shop $shop)
    {
        $this->assertAssignedShop($request, $shop);

        if (! in_array($shop->status, [Shop::STATUS_ACTIVE, Shop::STATUS_PENDING], true)) {
            throw ValidationException::withMessages(['shop' => $shop->name.' is '.strtolower($shop->statusLabel()).' and cannot be visited.']);
        }

        $data = $request->validate([
            'purpose' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $visit = $this->visits->checkIn($request->user(), $shop, $data);

        return redirect()->route('field.visit.show', $visit)->with('success', 'Checked in at '.$shop->name);
    }

    public function showVisit(Request $request, ShopVisit $visit)
    {
        abort_unless($visit->salesman_id === $request->user()->id, 403);

        $visit->load(['shop.priceGroup', 'order']);
        $group = $visit->shop?->priceGroup;

        $catalog = Product::query()
            ->with(['prices', 'category'])
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category?->name ?? 'Other',
                'price' => $p->priceForGroup($group),
                'stock' => $p->availableStock(),
                'image' => $p->imageUrl(),
            ])
            ->values();

        return view('field.visit', [
            'visit' => $visit,
            'canOrder' => $visit->shop?->status === Shop::STATUS_ACTIVE,
            'catalog' => $catalog,
            'categories' => $catalog->pluck('category')->unique()->sort()->values(),
        ]);
    }

    public function checkOut(Request $request, ShopVisit $visit)
    {
        abort_unless($visit->salesman_id === $request->user()->id, 403);

        $data = $request->validate([
            'outcome' => ['required', 'in:order_taken,no_order,closed,follow_up'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $visit = $this->visits->checkOut($visit, $data, $request->user());

        return redirect()->route('field.dashboard')
            ->with('success', 'Visit at '.$visit->shop?->name.' closed · '.$visit->outcomeLabel().'.');
    }

    public function submitOrder(Request $request, ShopVisit $visit)
    {
        abort_unless($visit->salesman_id === $request->user()->id, 403);

        if (! $visit->isOpen()) {
            throw ValidationException::withMessages(['visit' => 'This visit is already closed. Check in again to take a new order.']);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $order = $this->orders->collectFromSalesman(
            $request->user(),
            $visit->shop,
            $data['items'],
            $visit,
            $data['notes'] ?? null,
        );

        $this->visits->checkOut($visit->fresh(), ['outcome' => ShopVisit::OUTCOME_ORDER_TAKEN], $request->user());

        return redirect()
            ->route('field.orders.show', $order)
            ->with('success', 'Order '.$order->number.' sent for approval. Visit closed — you can check in at your next shop.');
    }

    public function orders(Request $request)
    {
        $filters = [
            'waiting' => [Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE],
            'active' => [Order::STATUS_APPROVED, Order::STATUS_PICKING, Order::STATUS_PICKED, Order::STATUS_PACKED, Order::STATUS_DISPATCHED],
            'delivered' => [Order::STATUS_DELIVERED],
            'rejected' => [Order::STATUS_REJECTED, Order::STATUS_CANCELLED],
        ];
        $filter = array_key_exists($request->query('status'), $filters) ? $request->query('status') : null;

        $orders = Order::query()
            ->with('shop')
            ->where('salesman_id', $request->user()->id)
            ->when($filter, fn ($q) => $q->whereIn('status', $filters[$filter]))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $byStatus = Order::query()->where('salesman_id', $request->user()->id)
            ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $counts = collect($filters)->map(fn (array $statuses) => (int) collect($statuses)->sum(fn ($s) => $byStatus[$s] ?? 0))
            ->put('all', (int) $byStatus->sum());

        return view('field.orders', compact('orders', 'filter', 'counts'));
    }

    public function showOrder(Request $request, Order $order)
    {
        abort_unless($order->salesman_id === $request->user()->id, 403);

        $order->load(['shop', 'items', 'visit']);

        return view('field.order-show', compact('order'));
    }

    public function earnings(Request $request)
    {
        $user = $request->user();
        $base = Commission::query()->where('salesman_id', $user->id)->where('status', '!=', Commission::STATUS_REJECTED);
        $month = (clone $base)->where('year', now()->year)->where('month', now()->month);

        return view('field.earnings', [
            'rule' => CommissionRule::activeDefault(),
            'monthEarned' => (float) (clone $month)->sum('commission_amount'),
            'monthPending' => (float) (clone $month)->whereIn('status', [Commission::STATUS_ACCRUED, Commission::STATUS_APPROVED])->sum('commission_amount'),
            'totalPaid' => (float) (clone $base)->where('status', Commission::STATUS_PAID)->sum('commission_amount'),
            'awaitingPayout' => (float) (clone $base)->whereIn('status', [Commission::STATUS_ACCRUED, Commission::STATUS_APPROVED])->sum('commission_amount'),
            'commissions' => (clone $base)->with(['payment.shop', 'order.shop'])->latest()->limit(30)->get(),
            'rewards' => Reward::query()->where('salesman_id', $user->id)
                ->where('status', '!=', Reward::STATUS_CANCELLED)->latest()->limit(10)->get(),
        ]);
    }

    private function openVisit(User $user): ?ShopVisit
    {
        return ShopVisit::query()->where('salesman_id', $user->id)->whereNull('checked_out_at')->first();
    }

    /** @return Collection<int, Shop> active and pending assigned shops, each with last_visit_at and visited_today */
    private function shopsWithVisits(User $user): Collection
    {
        $lastVisits = ShopVisit::query()
            ->where('salesman_id', $user->id)
            ->selectRaw('shop_id, MAX(checked_in_at) as last_at')
            ->groupBy('shop_id')
            ->pluck('last_at', 'shop_id');

        return $user->assignedShops()
            ->with(['territory', 'priceGroup'])
            ->whereIn('status', [Shop::STATUS_ACTIVE, Shop::STATUS_PENDING])
            ->orderBy('name')
            ->get()
            ->each(function (Shop $shop) use ($lastVisits) {
                $last = $lastVisits[$shop->id] ?? null;
                $shop->last_visit_at = $last ? Carbon::parse($last) : null;
                $shop->visited_today = $shop->last_visit_at?->isToday() ?? false;
            });
    }

    /** @return Collection<int, PartnerInquiry> accepted applications still waiting for this salesman to create the owner's login */
    private function loginsToCreate(User $user): Collection
    {
        return PartnerInquiry::query()
            ->with('shop')
            ->where('submitted_by', $user->id)
            ->where('status', PartnerInquiry::STATUS_CONVERTED)
            ->whereNull('portal_email')
            ->latest('reviewed_at')
            ->get();
    }

    /** @return Collection<int, Shop> shops this salesman looks after that have no portal login yet */
    private function partnerCandidateShops(User $user): Collection
    {
        return Shop::query()
            ->where(fn ($q) => $q->where('assigned_salesman_id', $user->id)->orWhere('created_by', $user->id))
            ->where('status', '!=', Shop::STATUS_REJECTED)
            ->whereDoesntHave('users')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'owner_name', 'phone', 'email', 'city', 'status']);
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
