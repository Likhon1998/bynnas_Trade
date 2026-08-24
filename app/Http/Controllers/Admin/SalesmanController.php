<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Territory;
use App\Models\User;
use App\Services\SalesmanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesmanController extends Controller
{
    public function __construct(private SalesmanService $salesmen) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('salesmen.view'), 403);

        $salesmen = $this->salesmen->salesmanQuery()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('salesmanProfile', fn ($p) => $p->where('employee_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->territory_id, function ($q, $territoryId) {
                $q->whereHas('salesmanProfile', fn ($p) => $p->where('territory_id', $territoryId));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $territories = Territory::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.salesmen.index', compact('salesmen', 'territories'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('salesmen.create'), 403);

        return view('admin.salesmen.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('salesmen.create'), 403);

        $data = $this->validated($request);
        $salesman = $this->salesmen->create($data, $request->input('shop_ids', []), $request->user());

        return redirect()->route('salesmen.show', $salesman)->with('success', 'Salesman created. Field login is ready.');
    }

    public function show(Request $request, User $salesman)
    {
        abort_unless($request->user()->can('salesmen.view'), 403);
        abort_unless($salesman->portal === User::PORTAL_SALESMAN, 404);

        $salesman->load([
            'salesmanProfile.territory',
            'assignedShops.territory',
            'visits' => fn ($q) => $q->latest()->limit(10),
            'salesmanOrders' => fn ($q) => $q->latest()->limit(10),
        ]);

        $monthOrders = $salesman->salesmanOrders()
            ->whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year)
            ->sum('total');

        return view('admin.salesmen.show', compact('salesman', 'monthOrders'));
    }

    public function edit(Request $request, User $salesman)
    {
        abort_unless($request->user()->can('salesmen.edit'), 403);
        abort_unless($salesman->portal === User::PORTAL_SALESMAN, 404);

        $salesman->load(['salesmanProfile', 'assignedShops']);

        return view('admin.salesmen.edit', array_merge($this->formData(), compact('salesman')));
    }

    public function update(Request $request, User $salesman)
    {
        abort_unless($request->user()->can('salesmen.edit'), 403);
        abort_unless($salesman->portal === User::PORTAL_SALESMAN, 404);

        $data = $this->validated($request, $salesman);
        $this->salesmen->update($salesman, $data, $request->input('shop_ids', []), $request->user());

        return redirect()->route('salesmen.show', $salesman)->with('success', 'Salesman updated.');
    }

    private function formData(): array
    {
        return [
            'territories' => Territory::query()->where('is_active', true)->orderBy('name')->get(),
            'shops' => Shop::query()->where('status', Shop::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name', 'code', 'assigned_salesman_id']),
        ];
    }

    private function validated(Request $request, ?User $salesman = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($salesman?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$salesman ? 'nullable' : 'required', 'string', 'min:8'],
            'employee_code' => ['nullable', 'string', 'max:40', Rule::unique('salesman_profiles', 'employee_code')->ignore($salesman?->salesmanProfile?->id)],
            'territory_id' => ['nullable', 'exists:territories,id'],
            'monthly_target' => ['nullable', 'numeric', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
