<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('warehouses.view'), 403);

        $warehouses = Warehouse::query()
            ->with('manager')
            ->withCount('stocks')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('warehouses.create'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->boolean('is_default')) {
            Warehouse::query()->update(['is_default' => false]);
        }

        Warehouse::query()->create($data + [
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Warehouse created.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        abort_unless($request->user()->can('warehouses.edit'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->boolean('is_default')) {
            Warehouse::query()->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update($data + [
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Warehouse updated.');
    }
}
