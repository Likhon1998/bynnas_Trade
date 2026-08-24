<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('suppliers.view'), 403);

        $suppliers = Supplier::query()
            ->withCount(['purchases', 'shipments'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('suppliers.create'), 403);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:160'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]);

        Supplier::query()->create($data + [
            'code' => $data['code'] ?: $this->purchases->nextSupplierCode(),
            'is_active' => true,
            'country' => $data['country'] ?? 'China',
        ]);

        return back()->with('success', 'Supplier created.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->can('suppliers.edit'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:160'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplier->update($data + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Supplier updated.');
    }
}
