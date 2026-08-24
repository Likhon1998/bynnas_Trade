<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'brand'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $data = $this->validated($request);
        $groupPrices = $request->input('group_prices', []);

        $product = $this->products->create($data, $request->user(), $groupPrices);

        return redirect()->route('products.index')->with('success', "Product {$product->sku} created.");
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->load('prices');

        return view('admin.products.edit', array_merge($this->formData(), compact('product')));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $this->products->update(
            $product,
            $this->validated($request, $product),
            $request->user(),
            $request->input('group_prices', []),
        );

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'model' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'landed_cost' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'dealer_price' => ['nullable', 'numeric', 'min:0'],
            'distributor_price' => ['nullable', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_selling_price' => ['nullable', 'numeric', 'min:0'],
            'warranty' => ['nullable', 'string', 'max:120'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'stock_on_hand' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'discontinued'])],
            'group_prices' => ['nullable', 'array'],
            'group_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'priceGroups' => PriceGroup::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
