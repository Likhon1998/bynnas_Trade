@extends('layouts.app')
@section('title', 'Create Order')
@section('content')
    <x-page-header title="Create Order" subtitle="Submitted to Super Admin for audit" />
    <div class="card" style="padding:22px;max-width:920px">
        <form class="form-grid" action="{{ route('orders.index') }}" method="get">
            <div>
                <label class="label">Shop</label>
                <select class="select" style="width:100%">
                    @foreach ($shops as $shop)
                        <option>{{ $shop['name'] }} — {{ $shop['city'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Salesman</label>
                <select class="select" style="width:100%">
                    @foreach ($salesmen as $row)
                        <option>{{ $row['name'] }} — {{ $row['territory'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Source</label>
                <select class="select" style="width:100%"><option>Salesman visit</option><option>Shop portal</option><option>Phone collection</option></select>
            </div>
            <div>
                <label class="label">Requested delivery</label>
                <input class="input" style="width:100%" type="date" value="2025-06-02">
            </div>
            <div class="form-span">
                <label class="label">Line items</label>
                <div class="table-wrap" style="border:1px solid var(--line);border-radius:12px">
                    <table class="data">
                        <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Wholesale</th></tr></thead>
                        <tbody>
                            @foreach ($products as $i => $product)
                                @if ($i > 2) @continue @endif
                                <tr>
                                    <td>{{ $product['sku'] }}</td>
                                    <td>{{ $product['name'] }}</td>
                                    <td><input class="input" style="width:80px;height:34px" value="{{ [24, 12, 8][$i] }}"></td>
                                    <td>{{ \App\Support\DemoData::taka($product['price']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="form-span">
                <label class="label">Visit / customer notes</label>
                <textarea class="textarea" rows="3" placeholder="Shop visit notes, contact person, delivery instruction"></textarea>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('orders.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Submit for Super Admin audit</button>
            </div>
        </form>
    </div>
@endsection
