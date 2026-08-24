@extends('portal.layouts.app')
@section('title', 'My shop')
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>My shop</strong> / Profile & credit</div>
    </div>
    <div class="grid-2">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">{{ $shop->name }}</div>
            <p class="muted">Owner: {{ $shop->owner_name }}</p>
            <p class="muted">City: {{ $shop->city ?: '—' }}</p>
            <p class="muted">Territory: {{ $shop->territory?->name ?: '—' }}</p>
            <p class="muted">Salesman: {{ $shop->assignedSalesman?->name ?: 'Unassigned' }}</p>
            <p class="muted">{{ $shop->address }}</p>
        </div>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">Credit summary</div>
            <p class="muted">Credit limit: <strong style="color:var(--text)">{{ \App\Support\DemoData::taka($shop->credit_limit) }}</strong></p>
            <p class="muted">Outstanding: <strong style="color:var(--text)">{{ \App\Support\DemoData::taka($shop->outstanding_balance) }}</strong></p>
            <p class="muted">Available: <strong style="color:var(--text)">{{ \App\Support\DemoData::taka($shop->availableCredit()) }}</strong></p>
            <p class="muted">Payment terms: {{ $shop->payment_terms_days }} days</p>
            <p class="muted">Price group: {{ $shop->priceGroup?->name ?: 'Standard' }}</p>
        </div>
    </div>
@endsection
