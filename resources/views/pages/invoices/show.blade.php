@php $invoice = collect($invoices)->firstWhere('no', $id) ?? $invoices[0]; @endphp
@extends('layouts.app')
@section('title', $invoice['no'])
@section('content')
    <x-page-header title="{{ $invoice['no'] }}" subtitle="{{ $invoice['shop'] }}">
        <x-slot:tools><x-badge :status="$invoice['status']" /></x-slot:tools>
    </x-page-header>
    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([['Invoice total', \App\Support\DemoData::taka($invoice['amount'])], ['Collected', \App\Support\DemoData::taka($invoice['paid'])], ['Outstanding', \App\Support\DemoData::taka($invoice['amount'] - $invoice['paid'])], ['Due date', $invoice['due']]] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:20px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>
    <div class="card" style="padding:18px">
        <div class="section-title">Linked order</div>
        <p class="muted">Raised from {{ $invoice['order'] }}. Credit utilisation and salesman commission post after Super Admin confirms collection.</p>
    </div>
@endsection
