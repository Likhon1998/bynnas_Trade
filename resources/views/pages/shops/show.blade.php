@php $shop = collect($shops)->firstWhere('id', $id) ?? $shops[0]; @endphp
@extends('layouts.app')
@section('title', $shop['name'])
@section('content')
    <x-page-header title="{{ $shop['name'] }}" subtitle="{{ $shop['id'] }}">
        <x-slot:tools>
            <x-badge :status="$shop['status']" />
        </x-slot:tools>
    </x-page-header>

    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([['Credit limit', \App\Support\DemoData::taka($shop['credit'])], ['Outstanding', \App\Support\DemoData::taka($shop['outstanding'])], ['Orders', $shop['orders']], ['Salesman', $shop['salesman']]] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:20px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>

    <div class="card" style="padding:18px">
        <div class="section-title" style="margin-bottom:10px">Portal access</div>
        <p class="muted">Owner {{ $shop['owner'] }} in {{ $shop['city'] }} can view assigned wholesale prices, available stock, invoices, credit utilisation and returns. Orders from this shop require Super Admin audit before reservation.</p>
    </div>
@endsection
