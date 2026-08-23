@php $row = collect($salesmen)->firstWhere('id', $id) ?? $salesmen[0]; @endphp
@extends('layouts.app')
@section('title', $row['name'])
@section('content')
    <x-page-header title="{{ $row['name'] }}" subtitle="{{ $row['territory'] }}">
        <x-slot:tools><x-badge :status="$row['status']" /></x-slot:tools>
    </x-page-header>
    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([['Assigned shops', $row['shops']], ['Orders this month', $row['orders']], ['Target', \App\Support\DemoData::taka($row['target'])], ['Commission accrued', \App\Support\DemoData::taka($row['commission'])]] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:20px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>
    <div class="card" style="padding:18px">
        <div class="section-title">Performance</div>
        <p class="muted">Achievement {{ \App\Support\DemoData::taka($row['achieved']) }} against a monthly target of {{ \App\Support\DemoData::taka($row['target']) }}. Visit logs, collections and pending approvals will bind here once the backend is connected.</p>
    </div>
@endsection
