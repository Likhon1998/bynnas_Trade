@extends('field.layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Today')
@section('content')
    <div class="stat-row">
        <div class="stat-card"><div class="label">Visits today</div><div class="value">{{ $todayVisits }}</div></div>
        <div class="stat-card"><div class="label">Pending audit</div><div class="value">{{ $pendingOrders }}</div></div>
        <div class="stat-card"><div class="label">Month total</div><div class="value" style="font-size:16px">{{ \App\Support\DemoData::taka($monthTotal) }}</div></div>
        <div class="stat-card"><div class="label">Target</div><div class="value" style="font-size:16px">{{ \App\Support\DemoData::taka($salesTarget?->target_amount ?? $salesmanProfile?->monthly_target ?? 0) }}</div></div>
        <div class="stat-card"><div class="label">Achieved</div><div class="value" style="font-size:16px">{{ \App\Support\DemoData::taka($salesTarget?->achieved_amount ?? $monthTotal) }}</div></div>
    </div>

    @if ($openVisit)
        <div class="open-banner">
            <div style="font-weight:700;margin-bottom:4px">Open visit · {{ $openVisit->shop?->name }}</div>
            <div style="font-size:13px;margin-bottom:10px">Checked in {{ $openVisit->checked_in_at?->diffForHumans() }}</div>
            <a class="btn btn-primary btn-block" href="{{ route('field.visit.show', $openVisit) }}">Continue visit / collect order</a>
        </div>
    @endif

    <div style="font-weight:700;margin:8px 0 10px;font-size:14px">Assigned shops</div>
    @forelse ($shops as $shop)
        <div class="shop-card">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                <div>
                    <div style="font-weight:700">{{ $shop->name }}</div>
                    <div class="muted" style="font-size:12px">{{ $shop->code }} · {{ $shop->city ?: $shop->territory?->name }}</div>
                </div>
                @unless ($openVisit)
                    <form method="post" action="{{ route('field.shops.check-in', $shop) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit" style="white-space:nowrap">Check in</button>
                    </form>
                @endunless
            </div>
        </div>
    @empty
        <div class="list-card muted">No shops assigned. Ask admin to assign shops.</div>
    @endforelse

    <div style="font-weight:700;margin:16px 0 10px;font-size:14px">Recent orders</div>
    @forelse ($recentOrders as $order)
        <a class="list-card" href="{{ route('field.orders.show', $order) }}" style="display:block;text-decoration:none;color:inherit">
            <div style="display:flex;justify-content:space-between">
                <strong>{{ $order->number }}</strong>
                <span>{{ \App\Support\DemoData::taka($order->total) }}</span>
            </div>
            <div class="muted" style="font-size:12px;margin-top:4px">{{ $order->shop?->name }} · {{ $order->statusLabel() }}</div>
        </a>
    @empty
        <div class="list-card muted">No orders collected yet.</div>
    @endforelse
@endsection
