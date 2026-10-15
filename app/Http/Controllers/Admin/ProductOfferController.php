<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Http\Request;

class ProductOfferController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('products.edit') || $request->user()->can('products.manage_price'), 403);

        $offers = ProductOffer::query()
            ->with(['product', 'creator'])
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->boolean('active_only'), fn ($q) => $q->currentlyActive())
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $products = Product::query()->where('status', Product::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.offers.index', compact('offers', 'products'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('products.edit') || $request->user()->can('products.manage_price'), 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'title' => ['required', 'string', 'max:160'],
            'offer_price' => ['required', 'numeric', 'min:0.01'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        ProductOffer::query()->create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Offer created.');
    }

    public function update(Request $request, ProductOffer $offer)
    {
        abort_unless($request->user()->can('products.edit') || $request->user()->can('products.manage_price'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'offer_price' => ['required', 'numeric', 'min:0.01'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $offer->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $offer->is_active),
        ]);

        return back()->with('success', 'Offer updated.');
    }

    public function destroy(Request $request, ProductOffer $offer)
    {
        abort_unless($request->user()->can('products.edit') || $request->user()->can('products.manage_price'), 403);

        $offer->update(['is_active' => false]);

        return back()->with('success', 'Offer deactivated.');
    }
}
