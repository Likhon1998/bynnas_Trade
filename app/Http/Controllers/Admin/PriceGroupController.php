<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceGroup;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PriceGroupController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        abort_unless(auth()->user()?->can('price_groups.view'), 403);

        $groups = PriceGroup::query()->withCount('shops')->orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.price-groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('price_groups.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:price_groups,name'],
            'code' => ['nullable', 'string', 'max:30', 'unique:price_groups,code'],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            PriceGroup::query()->update(['is_default' => false]);
        }

        $group = PriceGroup::query()->create([
            ...$data,
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        $this->auditLogger->log('price_groups', 'created', "Created price group {$group->name}", null, null, $data, $request->user());

        return back()->with('success', 'Price group created.');
    }

    public function update(Request $request, PriceGroup $priceGroup)
    {
        abort_unless(auth()->user()?->can('price_groups.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:price_groups,name,'.$priceGroup->id],
            'code' => ['nullable', 'string', 'max:30', 'unique:price_groups,code,'.$priceGroup->id],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            PriceGroup::query()->where('id', '!=', $priceGroup->id)->update(['is_default' => false]);
        }

        $priceGroup->update([
            ...$data,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Price group updated.');
    }
}
