@php $order = collect($orders)->firstWhere('no', $id) ?? $orders[0]; @endphp
@extends('layouts.app')
@section('title', $order['no'])
@section('content')
    <x-page-header title="{{ $order['no'] }}" subtitle="{{ $order['shop'] }} · {{ $order['salesman'] }}">
        <x-slot:tools>
            <x-badge :status="$order['status']" />
            @if ($order['status'] === 'Pending Approval')
                <button class="btn btn-ghost" type="button">Return for correction</button>
                <button class="btn btn-primary" type="button">Approve & reserve stock</button>
            @endif
        </x-slot:tools>
    </x-page-header>

    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([['Amount', \App\Support\DemoData::taka($order['amount'])], ['Source', $order['source']], ['Date', $order['date']], ['Next step', $order['status'] === 'Pending Approval' ? 'Super Admin audit' : 'Warehouse picking']] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:18px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid-2">
        <div class="card" style="padding:8px 0 0">
            <div class="section-title" style="padding:10px 16px">Order lines</div>
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Amount</th></tr></thead>
                <tbody>
                    @foreach ($products as $i => $product)
                        @if ($i > 2) @continue @endif
                        <tr>
                            <td>{{ $product['sku'] }}</td>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ [12, 6, 4][$i] }}</td>
                            <td>{{ \App\Support\DemoData::taka($product['price'] * [12, 6, 4][$i]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">Fulfilment pipeline</div>
            <p class="muted" style="margin-top:0">Pending Approval → Approved → Stock reservation → Warehouse picking → Packing → Dispatch → Delivery → Payment → Completion.</p>
            <ol class="muted" style="padding-left:18px;line-height:1.8">
                <li>Super Admin audits shop credit and stock.</li>
                <li>Available units are reserved against this order.</li>
                <li>Dhaka Central Warehouse picks and packs.</li>
                <li>Invoice is raised; collections post to the shop ledger.</li>
            </ol>
        </div>
    </div>
@endsection
