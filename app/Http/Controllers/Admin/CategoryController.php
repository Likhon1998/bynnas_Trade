<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\CatalogEntry;
use App\Support\CatalogLogo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('categories.view'), 403);

        $categories = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        abort_unless(
            $request->user()->can('categories.create') || $request->user()->can('products.create'),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'logo' => CatalogLogo::rules(),
        ]);

        $category = CatalogEntry::createOrRestore(Category::class, $data['name'], [
            'description' => $data['description'] ?? null,
        ]);
        CatalogLogo::apply($category, $request->file('logo'));

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'logo_url' => $category->fresh()->logoUrl(),
                'message' => 'Category created.',
            ]);
        }

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($request->user()->can('categories.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => CatalogLogo::rules(),
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $category->update([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);
        CatalogLogo::apply($category, $request->file('logo'), $request->boolean('remove_logo'));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($request->user()->can('categories.delete'), 403);

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category is used by products. Reassign those products first.',
            ]);
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
