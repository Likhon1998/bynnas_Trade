@extends('portal.layouts.app')
@section('title', 'Place order')
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>Orders</strong> / Place wholesale order</div>
        <a class="btn btn-ghost" href="{{ route('portal.orders') }}">My orders</a>
    </div>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px;background:#fff7ed;color:#9a3412;font-size:13px">
        Orders go to Super Admin audit first. Stock is reserved only after approval.
    </div>

    <form class="card" style="padding:16px" method="post" action="{{ route('portal.orders.store') }}">
        @csrf
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>Your price</th><th>Stock</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <x-product-thumb :product="$product" :size="48" />
                                    <div>
                                        <div style="font-weight:600">{{ $product->name }}</div>
                                        <div class="muted" style="font-size:12px">{{ $product->sku }}</div>
                                    </div>
                                </div>
                                <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                            </td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($product->priceForGroup($shop->priceGroup)) }}</td>
                            <td>{{ number_format($product->availableStock()) }}</td>
                            <td><input class="input" style="width:90px" type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="0"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="field" style="margin-top:16px">
            <label class="label">Notes</label>
            <textarea class="textarea" name="notes" rows="3" placeholder="Optional notes for Super Admin">{{ old('notes') }}</textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Submit for Super Admin audit</button>
        </div>
    </form>
@endsection
