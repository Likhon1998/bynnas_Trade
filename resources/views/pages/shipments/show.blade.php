@php $shipment = collect($shipments)->firstWhere('id', $id) ?? $shipments[0]; @endphp
@extends('layouts.app')
@section('title', $shipment['id'])
@section('content')
    <x-page-header title="{{ $shipment['id'] }}" subtitle="From {{ $shipment['origin'] }}">
        <x-slot:tools><x-badge :status="$shipment['status']" /></x-slot:tools>
    </x-page-header>
    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([['Carrier', $shipment['carrier']], ['Items', number_format($shipment['items'])], ['Freight / costs', \App\Support\DemoData::taka($shipment['cost'])], ['ETA', $shipment['eta']]] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:18px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>
    <div class="card" style="padding:8px 0 0">
        <div class="section-title" style="padding:10px 16px">Products on this shipment</div>
        <table class="data">
            <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Est. landed</th></tr></thead>
            <tbody>
                @foreach ($products as $i => $product)
                    @if ($i > 3) @continue @endif
                    <tr>
                        <td>{{ $product['sku'] }}</td>
                        <td>{{ $product['name'] }}</td>
                        <td>{{ [480, 240, 90, 620][$i] }}</td>
                        <td>{{ \App\Support\DemoData::taka($product['landed']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
