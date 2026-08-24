@extends('layouts.app')
@section('title', 'Receive stock')
@section('content')
    <x-page-header title="Receive stock" subtitle="Increase on-hand at a warehouse">
        <a class="btn btn-ghost" href="{{ route('inventory.index') }}">Back</a>
    </x-page-header>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <form class="card" style="padding:20px" method="post" action="{{ route('inventory.receive.store') }}">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label class="label">Warehouse</label>
                <select class="select" name="warehouse_id" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected($warehouse->is_default)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Product</label>
                <select class="select" name="product_id" required>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label class="label">Quantity</label><input class="input" type="number" min="1" name="qty" required></div>
            <div class="field"><label class="label">Reference</label><input class="input" name="reference" placeholder="PO / GRN"></div>
            <div class="field" style="grid-column:1/-1"><label class="label">Notes</label><textarea class="input" name="notes" rows="2"></textarea></div>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top:14px">Receive</button>
    </form>
@endsection
