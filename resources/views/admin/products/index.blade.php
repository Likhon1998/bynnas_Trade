@extends('layouts.app')
@section('title', 'Products')
@section('content')
    <x-page-header title="Products" subtitle="Catalogue, wholesale price and stock" action="{{ route('products.create') }}" action-label="Add Product">
        <x-slot:description>Shops see only their price-group wholesale rate. Cost and landed cost stay admin-only.</x-slot:description>
        <a class="btn btn-ghost" href="{{ route('price-groups.index') }}">Price groups</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search name or SKU">
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
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>On hand</th>
                        <th>Available</th>
                        <th>Wholesale</th>
                        <th>Landed</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td style="font-weight:700">{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?: '—' }}</td>
                            <td>{{ number_format($product->stock_on_hand) }}</td>
                            <td style="font-weight:700">{{ number_format($product->availableStock()) }}</td>
                            <td>{{ \App\Support\DemoData::taka($product->wholesale_price) }}</td>
                            <td>{{ \App\Support\DemoData::taka($product->landed_cost) }}</td>
                            <td><x-badge :status="$product->statusLabel()" /></td>
                            <td>
                                @can('update', $product)
                                    <a class="btn btn-ghost" style="padding:6px 10px" href="{{ route('products.edit', $product) }}">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted" style="padding:24px;text-align:center">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div style="padding:14px">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
