@extends('field.layouts.app')
@section('title', 'Shops')
@section('heading', 'My shops')
@section('content')
    @forelse ($shops as $shop)
        <div class="shop-card">
            <div style="font-weight:700">{{ $shop->name }}</div>
            <div class="muted" style="font-size:12px;margin:4px 0 10px">{{ $shop->code }} · {{ $shop->owner_name }} · {{ $shop->phone }}</div>
            <div class="muted" style="font-size:12px;margin-bottom:10px">Credit left: {{ \App\Support\DemoData::taka($shop->availableCredit()) }} · {{ $shop->priceGroup?->name }}</div>
            <form method="post" action="{{ route('field.shops.check-in', $shop) }}">
                @csrf
                <button class="btn btn-primary btn-block" type="submit">Check in & collect order</button>
            </form>
        </div>
    @empty
        <div class="list-card muted">No shops assigned.</div>
    @endforelse
@endsection
