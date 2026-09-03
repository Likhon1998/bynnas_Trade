@extends('portal.layouts.app')
@section('title', 'Edit '.$order->number)
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>Orders</strong> / Edit {{ $order->number }}</div>
        <div style="display:flex;gap:8px">
            <form method="post" action="{{ route('portal.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order before approval?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-ghost" type="submit" style="color:#b91c1c;border-color:#fecaca">Delete</button>
            </form>
            <a class="btn btn-ghost" href="{{ route('portal.orders.show', $order) }}">Cancel</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px;background:#fff7ed;color:#9a3412;font-size:13px">
        You can change quantities and notes until Super Admin approves. Stock is not reserved yet.
        Available credit: <strong>{{ \App\Support\DemoData::taka($shop->availableCredit()) }}</strong>
    </div>

    <form class="card" style="padding:16px" method="post" action="{{ route('portal.orders.update', $order) }}">
        @csrf
        @method('PUT')
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>Your price</th><th>Stock</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach ($products as $product)
                        @php $qty = old('items.'.$loop->index.'.quantity', $quantities[$product->id] ?? 0); @endphp
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
                            <td><input class="input" style="width:90px" type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="{{ $qty }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="field" style="margin-top:16px">
            <label class="label">Notes</label>
            <textarea class="textarea" name="notes" rows="3" placeholder="Optional notes for Super Admin">{{ old('notes', $order->notes) }}</textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="{{ route('portal.orders.show', $order) }}">Cancel</a>
        </div>
    </form>
@endsection
