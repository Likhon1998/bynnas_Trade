<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceGroup;
use App\Models\Shop;
use App\Models\Territory;
use App\Models\User;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function __construct(private ShopService $shops) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Shop::class);

        $shops = Shop::query()
            ->with(['territory', 'priceGroup', 'assignedSalesman'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->city, fn ($q, $city) => $q->where('city', $city))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shops.index', compact('shops'));
    }

    public function create()
    {
        $this->authorize('create', Shop::class);

        return view('admin.shops.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Shop::class);

        $data = $this->validated($request);

        $shop = $this->shops->create(
            $data,
            $request->user(),
            $request->boolean('issue_credentials') ? [
                'email' => $request->input('login_email', $data['email']),
                'password' => $request->input('login_password', '12345678'),
                'name' => $data['owner_name'],
                'phone' => $data['phone'] ?? null,
            ] : null,
        );

        return redirect()->route('shops.show', $shop)->with('success', 'Shop created successfully.');
    }

    public function show(Shop $shop)
    {
        $this->authorize('view', $shop);

        $shop->load(['territory', 'priceGroup', 'assignedSalesman', 'users']);

        return view('admin.shops.show', compact('shop'));
    }

    public function edit(Shop $shop)
    {
        $this->authorize('update', $shop);

        return view('admin.shops.edit', array_merge($this->formData(), compact('shop')));
    }

    public function update(Request $request, Shop $shop)
    {
        $this->authorize('update', $shop);

        $this->shops->update($shop, $this->validated($request, $shop), $request->user());

        return redirect()->route('shops.show', $shop)->with('success', 'Shop updated successfully.');
    }

    public function approve(Request $request, Shop $shop)
    {
        $this->authorize('approve', $shop);

        $this->shops->approve($shop, $request->user());

        return back()->with('success', 'Shop approved and activated.');
    }

    public function issueCredentials(Request $request, Shop $shop)
    {
        $this->authorize('manageCredentials', $shop);

        $data = $request->validate([
            'login_email' => ['required', 'email', 'max:190'],
            'login_password' => ['required', 'string', 'min:8'],
        ]);

        $this->shops->attachShopUser($shop, [
            'email' => $data['login_email'],
            'password' => $data['login_password'],
            'name' => $shop->owner_name,
            'phone' => $shop->phone,
        ], $request->user());

        if ($shop->status !== Shop::STATUS_ACTIVE) {
            $this->shops->approve($shop, $request->user());
        }

        return back()->with('success', 'Shop portal credentials issued.');
    }

    private function validated(Request $request, ?Shop $shop = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'owner_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'territory_id' => ['nullable', 'exists:territories,id'],
            'price_group_id' => ['nullable', 'exists:price_groups,id'],
            'assigned_salesman_id' => ['nullable', 'exists:users,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'status' => ['required', Rule::in([
                Shop::STATUS_PENDING, Shop::STATUS_ACTIVE, Shop::STATUS_ON_HOLD, Shop::STATUS_REJECTED,
            ])],
            'notes' => ['nullable', 'string'],
            'issue_credentials' => ['sometimes', 'boolean'],
            'login_email' => ['nullable', 'email'],
            'login_password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    private function formData(): array
    {
        return [
            'territories' => Territory::query()->where('is_active', true)->orderBy('name')->get(),
            'priceGroups' => PriceGroup::query()->where('is_active', true)->orderBy('name')->get(),
            'salesmen' => User::query()
                ->where('portal', User::PORTAL_SALESMAN)
                ->orWhereHas('roles', fn ($q) => $q->where('name', 'Salesman'))
                ->orderBy('name')
                ->get(),
        ];
    }
}
