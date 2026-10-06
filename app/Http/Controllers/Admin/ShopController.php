<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceGroup;
use App\Models\Shop;
use App\Models\Territory;
use App\Models\User;
use App\Rules\BangladeshPhone;
use App\Services\ShopService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function __construct(private ShopService $shops) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Shop::class);

        $shops = Shop::query()
            ->visibleTo($request->user())
            ->with(['territory', 'priceGroup', 'assignedSalesman', 'creator'])
            ->latest()
            ->limit(500)
            ->get();

        $rows = $shops->map(fn (Shop $shop) => [
            'id' => $shop->id,
            'code' => $shop->code,
            'name' => $shop->name,
            'owner' => $shop->owner_name ?: '',
            'city' => $shop->city ?: '',
            'price_group' => $shop->priceGroup?->name ?: '—',
            'salesman' => $shop->assignedSalesman?->name ?: '—',
            'source' => $shop->creator?->portal === User::PORTAL_SALESMAN ? 'field' : 'office',
            'added_by' => $shop->creator?->portal === User::PORTAL_SALESMAN ? $shop->creator->name : '',
            'added_on' => $shop->created_at?->format('d M Y'),
            'credit' => \App\Support\DemoData::taka($shop->credit_limit),
            'outstanding' => \App\Support\DemoData::taka($shop->outstanding_balance),
            'status' => $shop->status,
            'status_label' => $shop->statusLabel(),
            'status_badge' => match ($shop->status) {
                Shop::STATUS_ACTIVE => 'badge-active',
                Shop::STATUS_ON_HOLD => 'badge-hold',
                Shop::STATUS_REJECTED => 'badge-out',
                default => 'badge-pending',
            },
            'url' => route('shops.show', $shop),
        ])->values();

        $initialFilters = [
            'search' => (string) $request->get('search', ''),
            'status' => (string) $request->get('status', ''),
            'source' => $request->get('source') === 'field' ? 'field' : '',
        ];

        return view('admin.shops.index', compact('rows', 'initialFilters'));
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
        $data['phone'] = BangladeshPhone::normalize($data['phone']);
        $this->assertPhoneFree($data['phone']);
        $user = $request->user();
        if (! $user->can('shops.approve')) {
            $data['status'] = Shop::STATUS_PENDING;
        }
        if (! $user->can('shops.manage_credit')) {
            unset($data['credit_limit'], $data['payment_terms_days']);
        }

        $shop = $this->shops->create(
            $data,
            $request->user(),
            $request->boolean('issue_credentials') ? [
                'email' => $request->input('login_email') ?: $data['email'],
                'password' => $request->input('login_password'),
                'name' => $data['owner_name'],
                'phone' => $data['phone'] ?? null,
            ] : null,
        );

        return redirect()->route('shops.show', $shop)->with('success', 'Shop created successfully.');
    }

    private function assertPhoneFree(?string $phone, ?Shop $except = null): void
    {
        if (! $phone) {
            return;
        }

        $existing = Shop::query()
            ->where('phone', $phone)
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'phone' => "This phone number already belongs to {$existing->name} ({$existing->code}).",
            ]);
        }
    }

    public function show(Shop $shop)
    {
        $this->authorize('view', $shop);

        $shop->load(['territory', 'priceGroup', 'assignedSalesman', 'users', 'creator'])->loadCount('visits');

        $recentVisits = $shop->visits()->with(['salesman', 'order'])->latest('checked_in_at')->limit(8)->get();

        return view('admin.shops.show', compact('shop', 'recentVisits'));
    }

    public function edit(Shop $shop)
    {
        $this->authorize('update', $shop);

        return view('admin.shops.edit', array_merge($this->formData(), compact('shop')));
    }

    public function update(Request $request, Shop $shop)
    {
        $this->authorize('update', $shop);

        $data = $this->validated($request, $shop);
        $data['phone'] = BangladeshPhone::normalize($data['phone']);
        $this->assertPhoneFree($data['phone'], $shop);
        $user = $request->user();
        foreach (['credit_limit', 'payment_terms_days'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }
        }

        if ($data['status'] !== $shop->status && ! $user->can('shops.approve')) {
            throw ValidationException::withMessages(['status' => 'You are not allowed to change the shop status.']);
        }
        $creditChanged = (array_key_exists('credit_limit', $data) && round((float) $data['credit_limit'], 2) !== round((float) $shop->credit_limit, 2))
            || (array_key_exists('payment_terms_days', $data) && $data['payment_terms_days'] !== null && (int) $data['payment_terms_days'] !== (int) $shop->payment_terms_days);
        if ($creditChanged && ! $user->can('shops.manage_credit')) {
            throw ValidationException::withMessages(['credit_limit' => 'You are not allowed to change the credit limit or payment terms.']);
        }

        $this->shops->update($shop, $data, $user);

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
            'notify_whatsapp' => ['sometimes', 'boolean'],
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

        $whatsapp = app(WhatsAppService::class);
        $result = null;

        if ($request->boolean('notify_whatsapp', true)) {
            $result = $whatsapp->notifyApproval(
                $shop->phone,
                $shop->name,
                $data['login_email'],
                $data['login_password'],
            );
        } else {
            $message = $whatsapp->approvalMessage($shop->name, $data['login_email'], $data['login_password']);
            $result = [
                'ok' => true,
                'via' => 'manual',
                'chat_url' => $whatsapp->chatUrl($shop->phone, $message),
                'error' => null,
            ];
        }

        $flash = 'Shop portal credentials issued.';
        if ($result['ok'] && ($result['via'] ?? '') === 'meta') {
            $flash .= ' WhatsApp approval message sent.';
        } elseif ($result['ok'] && ($result['via'] ?? '') === 'log') {
            $flash .= ' WhatsApp message logged (set WHATSAPP_DRIVER=meta to send live).';
        } elseif (! empty($result['error'])) {
            $flash .= ' WhatsApp auto-send skipped: '.$result['error'];
        }

        return back()
            ->with('success', $flash)
            ->with('whatsapp_chat_url', $result['chat_url'] ?? null);
    }

    private function validated(Request $request, ?Shop $shop = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'owner_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', new BangladeshPhone(required: true)],
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
            'login_email' => ['nullable', 'email', Rule::requiredIf(fn () => ! $shop && $request->boolean('issue_credentials') && ! $request->filled('email'))],
            'login_password' => ['nullable', 'string', 'min:8', Rule::requiredIf(fn () => ! $shop && $request->boolean('issue_credentials'))],
        ], [
            'login_email.required' => 'Enter a login email (or a shop email) for the shop portal.',
            'login_password.required' => 'Set a password (8+ characters) for the shop portal login.',
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
