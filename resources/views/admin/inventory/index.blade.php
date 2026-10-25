@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
    <x-page-header title="Inventory" subtitle="Live stock across warehouses" action="{{ route('inventory.receive') }}" action-label="Receive Stock">
        <x-slot:description>On-hand value uses landed cost (or cost price). Totals follow the selected warehouse.</x-slot:description>
        <a class="btn btn-ghost" href="{{ route('inventory.ledger') }}">Ledger</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="order-page-summary">
        <div>
            <span>Total inventory value</span>
            <strong>{{ \App\Support\DemoData::taka($summary['value']) }}</strong>
        </div>
        <div>
            <span>Total products</span>
            <strong>{{ number_format($summary['products']) }}</strong>
        </div>
        <div>
            <span>Units on hand</span>
            <strong>{{ number_format($summary['units']) }}</strong>
        </div>
        <div>
            <span>Categories</span>
            <strong>{{ number_format(count($categoryStats)) }}</strong>
        </div>
    </div>

    @if ($categoryStats !== [])
        <div class="inventory-cats" aria-label="Products in each category">
            <a
                class="inventory-cat {{ ! request()->filled('category_id') ? 'is-active' : '' }}"
                href="{{ route('inventory.index', ['warehouse_id' => $warehouseId, 'search' => request('search')]) }}"
            >
                <span>All categories</span>
                <strong>{{ number_format($summary['products']) }} products</strong>
            </a>
            @foreach ($categoryStats as $stat)
                @php $statId = $stat['id'] ?? 0; @endphp
                <a
                    class="inventory-cat {{ request()->filled('category_id') && (int) $categoryId === (int) $statId ? 'is-active' : '' }}"
                    href="{{ route('inventory.index', ['warehouse_id' => $warehouseId, 'category_id' => $statId, 'search' => request('search')]) }}"
                >
                    <span>{{ $stat['name'] }}</span>
                    <strong>{{ number_format($stat['products']) }} {{ \Illuminate\Support\Str::plural('product', $stat['products']) }}</strong>
                    <em>{{ number_format($stat['units']) }} units · {{ \App\Support\DemoData::taka($stat['value']) }}</em>
                </a>
            @endforeach
        </div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="warehouse_id">
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search SKU or product">
            @if (request()->filled('category_id'))
                <input type="hidden" name="category_id" value="{{ $categoryId }}">
            @endif
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="num">On hand</th>
                        <th class="num">Reserved</th>
                        <th class="num">Available</th>
                        <th class="num">Unit cost</th>
                        <th class="num">Value</th>
                        <th>Warehouse</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        @php
                            $product = $stock->product;
                            $unit = $product?->inventoryUnitCost() ?? 0;
                            $line = round((int) $stock->qty_on_hand * $unit, 2);
                        @endphp
                        <tr>
                            <td>
                                <div class="product-cell">
                                    @if ($product)
                                        <x-product-thumb :product="$product" :size="48" />
                                    @endif
                                    <div>{{ $product?->name }}</div>
                                </div>
                            </td>
                            <td style="font-weight:700">{{ $product?->sku }}</td>
                            <td>{{ $product?->category?->name ?: 'Uncategorized' }}</td>
                            <td class="num">{{ number_format($stock->qty_on_hand) }}</td>
                            <td class="num">{{ number_format($stock->qty_reserved) }}</td>
                            <td class="num" style="font-weight:700">{{ number_format($stock->available()) }}</td>
                            <td class="num">{{ \App\Support\DemoData::taka($unit) }}</td>
                            <td class="num" style="font-weight:700">{{ \App\Support\DemoData::taka($line) }}</td>
                            <td>{{ $stock->warehouse?->code }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty">No stock rows yet. Receive stock or run Phase 5 seed.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stocks->hasPages())
            <div style="padding:12px 16px">{{ $stocks->links() }}</div>
        @endif
    </div>
@endsection
