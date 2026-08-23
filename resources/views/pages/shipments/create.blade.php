@extends('layouts.app')
@section('title', 'Add Shipment')
@section('content')
    <x-page-header title="Add Shipment" subtitle="Assign products to an inbound container" />
    <div class="card" style="padding:22px;max-width:860px">
        <form class="form-grid" action="{{ route('shipments.index') }}" method="get">
            <div>
                <label class="label">Origin</label>
                <input class="input" style="width:100%" value="Shenzhen, China">
            </div>
            <div>
                <label class="label">Carrier</label>
                <input class="input" style="width:100%" placeholder="COSCO / Maersk / Evergreen">
            </div>
            <div>
                <label class="label">Supplier</label>
                <select class="select" style="width:100%">
                    @foreach ($suppliers as $supplier)
                        <option>{{ $supplier['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">ETA</label>
                <input class="input" style="width:100%" type="date" value="2025-06-12">
            </div>
            <div>
                <label class="label">Freight (৳)</label>
                <input class="input" style="width:100%" value="185000">
            </div>
            <div>
                <label class="label">Customs & duty (৳)</label>
                <input class="input" style="width:100%" value="96000">
            </div>
            <div class="form-span">
                <label class="label">Assigned products</label>
                <p class="muted" style="margin:0 0 8px">Tick SKUs that travel on this shipment. Landed cost is allocated after receipt.</p>
                @foreach ($products as $product)
                    <label style="display:flex;gap:8px;align-items:center;margin-bottom:8px;font-size:13px">
                        <input type="checkbox" checked> {{ $product['sku'] }} — {{ $product['name'] }}
                    </label>
                @endforeach
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('shipments.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Create shipment</button>
            </div>
        </form>
    </div>
@endsection
