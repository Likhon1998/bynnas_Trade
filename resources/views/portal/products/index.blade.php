@extends('portal.layouts.app')
@section('title', 'Products')
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>Products</strong> / Your wholesale catalogue</div>
    </div>
    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search products">
            <select class="select" name="category_id">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Available stock</th>
                        <th>Your wholesale price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><x-product-thumb :product="$product" :size="56" /></td>
                            <td style="font-weight:700">{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?: '—' }}</td>
                            <td>{{ number_format($product->availableStock()) }}</td>
                            <td style="font-weight:800">{{ \App\Support\DemoData::taka($product->priceForGroup($shop->priceGroup)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">No products available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div style="padding:14px">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
