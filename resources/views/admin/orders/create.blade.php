@extends('layouts.app')
@section('title', 'Create Order')
@section('content')
    <x-page-header title="Create Order" subtitle="Submitted to Super Admin for audit">
        <a class="btn btn-ghost" href="{{ route('orders.index') }}">Back</a>
    </x-page-header>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
            <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card" style="padding:20px" method="post" action="{{ route('orders.store') }}">
        @csrf
        <div class="form-grid" style="margin-bottom:16px">
            <div class="field">
                <label class="label">Shop</label>
                <select class="select" name="shop_id" required>
                    <option value="">Select shop</option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->name }} ({{ $shop->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Salesman (optional)</label>
                <select class="select" name="salesman_id">
                    <option value="">Shop default / none</option>
                    @foreach ($salesmen as $salesman)
                        <option value="{{ $salesman->id }}" @selected(old('salesman_id') == $salesman->id)>{{ $salesman->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label class="label">Notes</label>
                <textarea class="input" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div style="font-weight:700;margin-bottom:10px">Lines</div>
        <div class="table-wrap" style="margin-bottom:16px">
            <table class="data">
                <thead><tr><th>Product</th><th>Available</th><th>Wholesale</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $product->name }}</div>
                                <div class="muted" style="font-size:12px">{{ $product->sku }}</div>
                                <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                            </td>
                            <td>{{ number_format($product->availableStock()) }}</td>
                            <td>{{ \App\Support\DemoData::taka($product->wholesale_price) }}</td>
                            <td><input class="input" style="width:90px" type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="0"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button class="btn btn-primary" type="submit">Submit for Super Admin audit</button>
    </form>
@endsection
