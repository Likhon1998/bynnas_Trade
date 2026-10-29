@extends('field.layouts.app')
@section('title', 'Home')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name;
    $toGo = max(0, $target - $achieved);
    $trendMax = max(1, $trend->max('total'));
    $weekTotal = $trend->sum('total');
    $short = fn (float $n) => $n >= 100000 ? round($n / 100000, 1).'L' : ($n >= 1000 ? round($n / 1000).'k' : (string) round($n));
    $mobileRoute = $toVisit->isNotEmpty()
        ? $toVisit
        : $routeShops->take(5);
@endphp
@section('heading', $greeting.', '.$firstName)
@section('subheading', now()->format('l, d F').' · '.$visitedCount.'/'.$shopCount.' shops today')
@section('actions')
    <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add shop</a>
    <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.orders.create') }}"><i data-lucide="shopping-bag"></i> New order</a>
@endsection
@section('content')
    <div class="f-home">
        <div class="f-quick f-mobile-only">
            <a class="f-btn f-btn-primary" href="{{ route('field.orders.create') }}"><i data-lucide="shopping-bag"></i> New order</a>
            <a class="f-btn f-btn-ghost" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add shop</a>
        </div>

        @include('field.partials.login-todo')

        @if (! empty($targetMet))
            <div class="f-flash ok">
                <i data-lucide="check-circle-2"></i>
                <span>Congratulations — you hit your monthly sales target!</span>
            </div>
        @endif

        {{-- Hero + 4 equal stats (no orphan cells) --}}
        <div class="f-home-hero f-kpi tone-violet">
            <div class="f-kpi-top">
                <span class="f-ico"><i data-lucide="trending-up"></i></span>
                <span class="f-kpi-label">Sales this month</span>
                @if ($progress !== null)<span class="f-kpi-badge">{{ $progress }}%</span>@endif
            </div>
            <div class="f-kpi-value">{{ \App\Support\DemoData::taka($monthTotal) }}</div>
            <div class="f-kpi-meta">
                @if ($progress !== null)
                    Delivered {{ \App\Support\DemoData::taka($achieved) }} of {{ \App\Support\DemoData::taka($target) }}
                    · @if ($toGo > 0)<b>{{ \App\Support\DemoData::taka($toGo) }}</b> to go · {{ $daysLeft }}d left @else <b>Target reached</b> @endif
                @else
                    No target set for this month
                @endif
                @if (($shopsOutstanding ?? 0) > 0)
                    · Baki {{ \App\Support\DemoData::taka($shopsOutstanding) }}
                @endif
            </div>
            <div class="f-kpi-bar"><span style="width: {{ max(2, $progress ?? 0) }}%"></span></div>
        </div>

        <div class="f-kpis f-home-stats">
            <a class="f-kpi tone-green" href="{{ route('field.earnings') }}">
                <div class="f-kpi-top"><span class="f-ico"><i data-lucide="wallet"></i></span><span class="f-kpi-label">Commission</span></div>
                <div class="f-kpi-value">{{ \App\Support\DemoData::taka($monthCommission) }}</div>
                <div class="f-kpi-meta">This month</div>
            </a>
            <div class="f-kpi tone-blue">
                <div class="f-kpi-top"><span class="f-ico"><i data-lucide="map-pin"></i></span><span class="f-kpi-label">Visits today</span></div>
                <div class="f-kpi-value">{{ $todayVisits }}</div>
                <div class="f-kpi-meta"><b>{{ $visitedCount }}/{{ $shopCount }}</b> covered</div>
            </div>
            <div class="f-kpi tone-amber">
                <div class="f-kpi-top"><span class="f-ico"><i data-lucide="shopping-bag"></i></span><span class="f-kpi-label">Orders today</span></div>
                <div class="f-kpi-value">{{ $todayOrderCount }}</div>
                <div class="f-kpi-meta"><b>{{ \App\Support\DemoData::taka($todayOrderTotal) }}</b></div>
            </div>
            <a class="f-kpi tone-rose" href="{{ route('field.orders', ['status' => 'waiting']) }}">
                <div class="f-kpi-top"><span class="f-ico"><i data-lucide="hourglass"></i></span><span class="f-kpi-label">Waiting</span></div>
                <div class="f-kpi-value">{{ $waitingApproval }}</div>
                <div class="f-kpi-meta">With the office</div>
            </a>
        </div>

        <div class="f-grid-main f-home-grid">
            <section class="f-panel f-home-route">
                <div class="f-panel-head">
                    <h2><span class="f-ico tone-blue"><i data-lucide="route"></i></span>Today's route</h2>
                    <span class="f-home-links">
                        <a href="{{ route('field.shops.create') }}">+ Shop</a>
                        <a href="{{ route('field.shops') }}">All ›</a>
                    </span>
                </div>
                <div class="f-table-wrap f-desktop-only">
                    <table class="f-table">
                        <thead><tr><th>Shop</th><th>Area</th><th>Last visit</th><th class="num">Baki</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($routeShops as $shop)
                                @include('field.partials.shop-row', ['shop' => $shop, 'openVisit' => $openVisit])
                            @empty
                                <tr><td colspan="5" class="empty">No shops on your route yet. <a href="{{ route('field.shops.create') }}">Add a shop</a> or take an order for any active shop.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="f-panel-body f-mobile-only f-home-route-mobile">
                    @forelse ($mobileRoute as $shop)
                        @include('field.partials.shop-card', ['shop' => $shop, 'openVisit' => $openVisit])
                    @empty
                        <div class="f-empty compact">
                            <i data-lucide="store"></i>
                            <strong>No shops yet</strong>
                            <a href="{{ route('field.shops.create') }}">Add your first shop</a>
                            or open <a href="{{ route('field.orders.create') }}">New order</a>.
                        </div>
                    @endforelse
                    @if ($toVisit->isEmpty() && $shopCount > 0)
                        <p class="f-home-note"><i data-lucide="check-circle-2"></i> All assigned shops visited today</p>
                    @endif
                </div>
            </section>

            <div class="f-home-side">
                <section class="f-panel">
                    <div class="f-panel-head">
                        <h2><span class="f-ico tone-violet"><i data-lucide="bar-chart-3"></i></span>Last 7 days</h2>
                        <span class="f-chip violet">{{ \App\Support\DemoData::taka($weekTotal) }}</span>
                    </div>
                    <div class="f-panel-body f-trend-wrap">
                        @if ($weekTotal <= 0)
                            <div class="f-trend-empty">No orders this week.<br><a href="{{ route('field.orders.create') }}">Start an order ›</a></div>
                        @endif
                        <div class="f-trend">
                            @foreach ($trend as $day)
                                <div class="f-trend-col {{ $loop->last ? 'today' : '' }}" title="{{ $day['label'] }}: {{ \App\Support\DemoData::taka($day['total']) }}">
                                    <em>{{ $day['total'] > 0 ? $short($day['total']) : '' }}</em>
                                    <div class="f-trend-bar {{ $day['total'] > 0 ? '' : 'zero' }}" style="height: {{ $day['total'] > 0 ? max(8, round($day['total'] / $trendMax * 100)) : 4 }}%"></div>
                                    <small>{{ $day['label'] }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="f-panel f-home-orders">
            <div class="f-panel-head">
                <h2><span class="f-ico tone-amber"><i data-lucide="receipt-text"></i></span>Recent orders</h2>
                <a href="{{ route('field.orders') }}">See all ›</a>
            </div>
            @if ($recentOrders->isEmpty())
                <div class="f-panel-body f-home-orders-body">
                    <div class="f-empty compact">
                        <i data-lucide="receipt-text"></i>
                        <strong>No orders yet</strong>
                        <a href="{{ route('field.orders.create') }}">Take your first order</a>
                    </div>
                </div>
            @else
                <div class="f-table-wrap">
                    <table class="f-table f-table-orders">
                        <thead>
                            <tr>
                                <th>Shop</th>
                                <th class="f-table-orders-wide">Order</th>
                                <th class="f-table-orders-wide">Date</th>
                                        <th class="num">Total</th>
                                        <th class="num">Baki</th>
                                        <th class="status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                @include('field.partials.order-table-row', ['order' => $order])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
