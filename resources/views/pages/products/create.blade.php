@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
    <x-page-header title="Add Product" subtitle="Link SKU to shipment and warehouse" />
    <div class="card" style="padding:22px;max-width:860px">
        <form class="form-grid" action="{{ route('products.index') }}" method="get">
            <div>
                <label class="label">Product name</label>
                <input class="input" style="width:100%" placeholder="e.g. Bluetooth Earbuds">
            </div>
            <div>
                <label class="label">SKU</label>
                <input class="input" style="width:100%" placeholder="BT-EB-01">
            </div>
            <div>
                <label class="label">Category</label>
                <select class="select" style="width:100%">
                    @foreach ($categories as $category)
                        <option>{{ $category['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Inbound shipment</label>
                <select class="select" style="width:100%">
                    @foreach ($shipments as $shipment)
                        <option>{{ $shipment['id'] }} — {{ $shipment['origin'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Wholesale price (৳)</label>
                <input class="input" style="width:100%" value="1250">
            </div>
            <div>
                <label class="label">Landed cost (৳)</label>
                <input class="input" style="width:100%" value="780">
            </div>
            <div>
                <label class="label">Warranty</label>
                <select class="select" style="width:100%"><option>6 months</option><option>12 months</option><option>No warranty</option></select>
            </div>
            <div>
                <label class="label">Default warehouse</label>
                <select class="select" style="width:100%">
                    @foreach ($warehouses as $warehouse)
                        <option>{{ $warehouse['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('products.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save product</button>
            </div>
        </form>
    </div>
@endsection
