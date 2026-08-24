@extends('layouts.app')
@section('title', 'Create PO')
@section('content')
    <x-page-header title="Create purchase order" subtitle="Source products from a China supplier">
        <a class="btn btn-ghost" href="{{ route('purchases.index') }}">Back</a>
    </x-page-header>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <form class="card" style="padding:20px" method="post" action="{{ route('purchases.store') }}">
        @csrf
        <div class="form-grid" style="margin-bottom:16px">
            <div class="field">
                <label class="label">Supplier</label>
                <select class="select" name="supplier_id" required>
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label class="label">Currency</label><input class="input" name="currency" value="CNY"></div>
            <div class="field"><label class="label">Exchange rate → BDT</label><input class="input" type="number" step="0.0001" min="0.0001" name="exchange_rate" value="16.5" required></div>
            <div class="field"><label class="label">Ordered on</label><input class="input" type="date" name="ordered_at" value="{{ now()->toDateString() }}"></div>
            <div class="field"><label class="label">Expected</label><input class="input" type="date" name="expected_at"></div>
            <div class="field" style="grid-column:1/-1"><label class="label">Notes</label><textarea class="input" name="notes" rows="2"></textarea></div>
        </div>

        <div style="font-weight:700;margin-bottom:10px">Lines (unit cost in foreign currency)</div>
        <div class="table-wrap" style="margin-bottom:16px">
            <table class="data">
                <thead><tr><th>Product</th><th>Qty</th><th>Unit cost (foreign)</th></tr></thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $product->name }}</div>
                                <div class="muted" style="font-size:12px">{{ $product->sku }}</div>
                                <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                            </td>
                            <td><input class="input" style="width:90px" type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="0"></td>
                            <td><input class="input" style="width:120px" type="number" step="0.01" min="0" name="items[{{ $loop->index }}][unit_cost_foreign]" value="{{ round($product->cost_price / 16.5, 2) }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary" type="submit">Create PO</button>
    </form>
@endsection
