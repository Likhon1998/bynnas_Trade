<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        abort_unless($request->user()->can('categories.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::query()->create([
            'name' => trim($data['name']),
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'message' => 'Category created.',
            ]);
        }

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($request->user()->can('categories.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

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
