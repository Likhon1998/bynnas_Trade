@extends('field.layouts.app')
@section('title', 'Home')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name;
    $toGo = max(0, $target - $monthTotal);
    $trendMax = max(1, $trend->max('total'));
    $weekTotal = $trend->sum('total');
    $short = fn (float $n) => $n >= 100000 ? round($n / 100000, 1).'L' : ($n >= 1000 ? round($n / 1000).'k' : (string) round($n));
@endphp
@section('heading', $greeting.', '.$firstName)
@section('subheading', now()->format('l, d F Y').' · '.$visitedCount.' of '.$shopCount.' shops covered today')
@section('actions')
    <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add shop</a>
    <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.orders') }}"><i data-lucide="receipt-text"></i> My orders</a>
    <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.shops') }}"><i data-lucide="map-pin"></i> Start a visit</a>
@endsection
@section('content')
    @include('field.partials.login-todo')

    <div class="f-kpis five">
        <div class="f-kpi tone-violet f-kpi-wide">
            <div class="f-kpi-top">
                <span class="f-ico"><i data-lucide="trending-up"></i></span>
                <span class="f-kpi-label">Sales this month</span>
                @if ($progress !== null)<span class="f-kpi-badge">{{ $progress }}%</span>@endif
            </div>
            <div class="f-kpi-value">{{ \App\Support\DemoData::taka($monthTotal) }}</div>
            <div class="f-kpi-meta">
                @if ($progress !== null)
                    Target {{ \App\Support\DemoData::taka($target) }} ·
                    @if ($toGo > 0)<b>{{ \App\Support\DemoData::taka($toGo) }}</b> to go · {{ $daysLeft }}d left @else <b>Target reached</b> @endif
                @else
                    No target set for this month
                @endif
            </div>
            <div class="f-kpi-bar"><span style="width: {{ max(2, $progress ?? 0) }}%"></span></div>
        </div>
        <a class="f-kpi tone-green" href="{{ route('field.earnings') }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="wallet"></i></span><span class="f-kpi-label">Commission</span></div>
            <div class="f-kpi-value">{{ \App\Support\DemoData::taka($monthCommission) }}</div>
            <div class="f-kpi-meta">Earned this month</div>
        </a>
        <div class="f-kpi tone-blue">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="map-pin"></i></span><span class="f-kpi-label">Visits today</span></div>
            <div class="f-kpi-value">{{ $todayVisits }}</div>
            <div class="f-kpi-meta"><b>{{ $visitedCount }}/{{ $shopCount }}</b> covered · <b>{{ $shopsAddedMonth }}</b> new shop{{ $shopsAddedMonth === 1 ? '' : 's' }} this month</div>
        </div>
        <div class="f-kpi tone-amber">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="shopping-bag"></i></span><span class="f-kpi-label">Orders today</span></div>
            <div class="f-kpi-value">{{ $todayOrderCount }}</div>
            <div class="f-kpi-meta">Worth <b>{{ \App\Support\DemoData::taka($todayOrderTotal) }}</b></div>
        </div>
        <a class="f-kpi tone-rose" href="{{ route('field.orders', ['status' => 'waiting']) }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="hourglass"></i></span><span class="f-kpi-label">Awaiting approval</span></div>
            <div class="f-kpi-value">{{ $waitingApproval }}</div>
            <div class="f-kpi-meta">Orders with the office</div>
        </a>
    </div>

    <div class="f-grid-main">
        <div>
            <section class="f-panel">
                <div class="f-panel-head">
                    <h2><span class="f-ico tone-blue"><i data-lucide="route"></i></span>Today's route</h2>
                    <span style="display:flex;gap:12px">
                        <a href="{{ route('field.shops.create') }}">+ Add shop</a>
                        <a href="{{ route('field.shops') }}">All shops ›</a>
                    </span>
                </div>
                <div class="f-table-wrap f-desktop-only">
                    <table class="f-table">
                        <thead><tr><th>Shop</th><th>Area</th><th>Last visit</th><th class="num">Credit left</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($routeShops as $shop)
                                @include('field.partials.shop-row', ['shop' => $shop, 'openVisit' => $openVisit])
                            @empty
                                <tr><td colspan="5" class="empty">No shops assigned yet. Ask your manager to assign shops to your route.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="f-panel-body f-mobile-only">
                    @forelse ($toVisit as $shop)
                        @include('field.partials.shop-card', ['shop' => $shop, 'openVisit' => $openVisit])
                    @empty
                        <div class="f-empty">
                            <i data-lucide="{{ $shopCount ? 'party-popper' : 'store' }}"></i>
                            <strong>{{ $shopCount ? 'All shops visited today' : 'No shops assigned yet' }}</strong>
                            {{ $shopCount ? 'Great work! Check your orders or revisit a shop.' : 'Ask your manager to assign shops to your route.' }}
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div>
            <section class="f-panel">
                <div class="f-panel-head">
                    <h2><span class="f-ico tone-violet"><i data-lucide="bar-chart-3"></i></span>Last 7 days</h2>
                    <span class="f-chip violet">{{ \App\Support\DemoData::taka($weekTotal) }}</span>
                </div>
                <div class="f-panel-body" style="position:relative">
                    @if ($weekTotal <= 0)
                        <div class="f-trend-empty">No orders in the last 7 days.<br><a href="{{ route('field.shops') }}">Start a visit ›</a></div>
                    @endif
                    <div class="f-trend">
                        @foreach ($trend as $i => $day)
                            <div class="f-trend-col {{ $loop->last ? 'today' : '' }}" title="{{ $day['label'] }}: {{ \App\Support\DemoData::taka($day['total']) }}">
                                <em>{{ $day['total'] > 0 ? $short($day['total']) : '' }}</em>
                                <div class="f-trend-bar {{ $day['total'] > 0 ? '' : 'zero' }}" style="height: {{ $day['total'] > 0 ? max(8, round($day['total'] / $trendMax * 100)) : 4 }}%"></div>
                                <small>{{ $day['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="f-panel">
                <div class="f-panel-head">
                    <h2><span class="f-ico tone-amber"><i data-lucide="receipt-text"></i></span>Recent orders</h2>
                    <a href="{{ route('field.orders') }}">See all ›</a>
                </div>
                <div class="f-panel-body" style="padding-top:10px;padding-bottom:4px">
                    @forelse ($recentOrders as $order)
                        @include('field.partials.order-row', ['order' => $order])
                    @empty
                        <div class="f-empty">
                            <i data-lucide="receipt-text"></i>
                            <strong>No orders yet</strong>
                            Check in at a shop to take your first order.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
