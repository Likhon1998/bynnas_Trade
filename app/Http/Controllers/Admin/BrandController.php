<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\CatalogEntry;
use App\Support\CatalogLogo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('brands.view'), 403);

        $brands = Brand::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        abort_unless(
            $request->user()->can('brands.create') || $request->user()->can('products.create'),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'logo' => CatalogLogo::rules(),
        ]);

        $brand = CatalogEntry::createOrRestore(Brand::class, $data['name']);
        CatalogLogo::apply($brand, $request->file('logo'));

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'id' => $brand->id,
                'name' => $brand->name,
                'logo_url' => $brand->fresh()->logoUrl(),
                'message' => 'Brand created.',
            ]);
        }

        return back()->with('success', 'Brand created.');
    }

    public function update(Request $request, Brand $brand)
    {
        abort_unless($request->user()->can('brands.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('brands', 'name')->ignore($brand->id)->whereNull('deleted_at')],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => CatalogLogo::rules(),
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $brand->update([
            'name' => trim($data['name']),
            'is_active' => $request->boolean('is_active', $brand->is_active),
        ]);
        CatalogLogo::apply($brand, $request->file('logo'), $request->boolean('remove_logo'));

        return back()->with('success', 'Brand updated.');
    }

    public function destroy(Request $request, Brand $brand)
    {
        abort_unless($request->user()->can('brands.delete'), 403);

        if ($brand->products()->exists()) {
            throw ValidationException::withMessages([
                'brand' => 'This brand is used by products. Reassign those products first.',
            ]);
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted.');
    }
}
