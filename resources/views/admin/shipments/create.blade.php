@extends('layouts.app')
@section('title', 'Add shipment')
@section('content')
    <x-page-header title="Add China shipment" subtitle="Link products, freight and customs">
        <a class="btn btn-ghost" href="{{ route('shipments.index') }}">Back</a>
    </x-page-header>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <form class="card" style="padding:20px" method="post" action="{{ route('shipments.store') }}">
        @csrf
        <div class="form-grid" style="margin-bottom:16px">
            <div class="field">
                <label class="label">From purchase (optional)</label>
                <select class="select" name="purchase_id">
                    <option value="">Manual lines</option>
                    @foreach ($purchases as $purchase)
                        <option value="{{ $purchase->id }}">{{ $purchase->number }} — {{ $purchase->supplier?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Supplier</label>
                <select class="select" name="supplier_id">
                    <option value="">Select</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Receive warehouse</label>
                <select class="select" name="warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected($warehouse->is_default)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label class="label">Origin</label><input class="input" name="origin" value="Shenzhen, China"></div>
            <div class="field"><label class="label">Carrier</label><input class="input" name="carrier"></div>
            <div class="field"><label class="label">Tracking</label><input class="input" name="tracking_ref"></div>
            <div class="field"><label class="label">Container</label><input class="input" name="container_no"></div>
            <div class="field"><label class="label">Shipped</label><input class="input" type="date" name="shipped_at" value="{{ now()->toDateString() }}"></div>
            <div class="field"><label class="label">ETA</label><input class="input" type="date" name="eta_at"></div>
            <div class="field"><label class="label">Freight (BDT)</label><input class="input" type="number" step="0.01" min="0" name="freight_cost" value="85000"></div>
            <div class="field"><label class="label">Customs (BDT)</label><input class="input" type="number" step="0.01" min="0" name="customs_duty" value="42000"></div>
            <div class="field"><label class="label">Insurance (BDT)</label><input class="input" type="number" step="0.01" min="0" name="insurance_cost" value="8000"></div>
            <div class="field"><label class="label">Other (BDT)</label><input class="input" type="number" step="0.01" min="0" name="other_cost" value="5000"></div>
        </div>

        <div style="font-weight:700;margin-bottom:10px">Products (ignored if PO selected)</div>
        <div class="table-wrap" style="margin-bottom:16px">
            <table class="data">
                <thead><tr><th>Product</th><th>Qty</th><th>Unit cost (BDT)</th></tr></thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>
                                {{ $product->name }} <span class="muted">({{ $product->sku }})</span>
                                <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                            </td>
                            <td><input class="input" style="width:90px" type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="0"></td>
                            <td><input class="input" style="width:120px" type="number" step="0.01" min="0" name="items[{{ $loop->index }}][unit_cost_bdt]" value="{{ $product->cost_price }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary" type="submit">Create shipment</button>
    </form>
@endsection
