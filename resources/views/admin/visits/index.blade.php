@extends('layouts.app')
@section('title', 'Field Activity')
@php
    $taka = fn ($n) => \App\Support\DemoData::taka($n);
    $maxVisits = max(1, $summary->max('visits'));
    $periodLabel = strtolower($periods[$period]);
    $query = fn (array $extra) => array_filter(array_merge(request()->only(['period', 'salesman_id', 'outcome', 'shop_id']), $extra), fn ($v) => $v !== null && $v !== '');
@endphp
@section('content')
    <x-page-header title="Field Activity" subtitle="Shop visits, orders and new shops by each salesman">
        <a class="btn btn-ghost" href="{{ route('shops.index', ['source' => 'field']) }}">Salesman-added shops</a>
        <a class="btn btn-ghost" href="{{ route('salesmen.index') }}">Salesmen</a>
    </x-page-header>

    <div class="fa-toolbar">
        <nav class="fa-seg" aria-label="Period">
            @foreach ($periods as $key => $label)
                <a href="{{ route('visits.index', $query(['period' => $key, 'page' => null])) }}" class="{{ $period === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <span class="muted" style="font-size:12px">{{ $totals['active'] }} of {{ $summary->count() }} salesmen visited shops {{ $period === 'all' ? 'so far' : $periodLabel }}</span>
    </div>

    <div class="fa-stats">
        <div class="card fa-stat fa-violet">
            <div class="fa-stat-label">Shop visits</div>
            <div class="fa-stat-value">{{ number_format($totals['visits']) }}</div>
            <div class="fa-stat-meta">{{ $periods[$period] }}</div>
        </div>
        <div class="card fa-stat fa-blue">
            <div class="fa-stat-label">Unique shops visited</div>
            <div class="fa-stat-value">{{ number_format($totals['shops']) }}</div>
            <div class="fa-stat-meta">Different shops reached</div>
        </div>
        <div class="card fa-stat fa-green">
            <div class="fa-stat-label">Orders taken</div>
            <div class="fa-stat-value">{{ number_format($totals['orders']) }}</div>
            <div class="fa-stat-meta">Worth {{ $taka($totals['order_value']) }}{{ $totals['visits'] ? ' · '.round($totals['with_order'] / $totals['visits'] * 100).'% of visits' : '' }}</div>
        </div>
        <a class="card fa-stat fa-amber" href="{{ route('shops.index', ['source' => 'field', 'status' => $totals['added_pending'] ? 'pending' : null]) }}">
            <div class="fa-stat-label">New shops added</div>
            <div class="fa-stat-value">{{ number_format($totals['added']) }}</div>
            <div class="fa-stat-meta">{{ $totals['added_pending'] ? $totals['added_pending'].' waiting for approval ›' : 'By salesmen in the field' }}</div>
        </a>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="fa-card-head">
            <div>
                <strong>By salesman</strong>
                <span class="muted" style="font-size:12px"> · {{ $periods[$period] }}</span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data fa-table">
                <thead>
                    <tr>
                        <th>Salesman</th>
                        <th>Territory</th>
                        <th style="min-width:160px">Visits</th>
                        <th class="num">Unique shops</th>
                        <th class="num">Orders</th>
                        <th class="num">Order value</th>
                        <th class="num">Visit → order</th>
                        <th class="num">Shops added</th>
                        <th>Last visit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary as $row)
                        <tr class="{{ (int) request('salesman_id') === $row['id'] ? 'fa-selected' : '' }}">
                            <td>
                                <a class="link" style="font-weight:600" href="{{ route('salesmen.show', $row['id']) }}">{{ $row['name'] }}</a>
                                @if ($row['code'])<div class="muted" style="font-size:11.5px">{{ $row['code'] }}</div>@endif
                            </td>
                            <td class="muted">{{ $row['territory'] ?: '—' }}</td>
                            <td>
                                <a class="fa-bar-cell" href="{{ route('visits.index', $query(['salesman_id' => $row['id'], 'page' => null])) }}#log" title="Show {{ $row['name'] }}'s visits">
                                    <span class="fa-bar"><span style="width: {{ $row['visits'] ? max(4, round($row['visits'] / $maxVisits * 100)) : 0 }}%"></span></span>
                                    <b>{{ $row['visits'] }}</b>
                                </a>
                            </td>
                            <td class="num">{{ $row['shops'] }}</td>
                            <td class="num">{{ $row['orders'] }}</td>
                            <td class="num" style="font-weight:600">{{ $taka($row['order_value']) }}</td>
                            <td class="num">
                                @if ($row['conversion'] !== null)
                                    <span class="fa-pill {{ $row['conversion'] >= 50 ? 'good' : ($row['conversion'] >= 25 ? 'mid' : 'low') }}">{{ $row['conversion'] }}%</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="num">
                                @if ($row['added'])
                                    <a class="link" href="{{ route('shops.index', ['source' => 'field', 'search' => $row['name']]) }}">{{ $row['added'] }}</a>
                                    @if ($row['added_pending'])<span class="fa-pill mid" title="Waiting for approval">{{ $row['added_pending'] }} pending</span>@endif
                                @else
                                    <span class="muted">0</span>
                                @endif
                            </td>
                            <td class="muted" style="white-space:nowrap">{{ $row['last_at'] ? $row['last_at']->diffForHumans() : 'No visits' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted" style="padding:24px;text-align:center">No salesmen yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="log">
        <div class="fa-card-head">
            <strong>Visit log</strong>
            <form method="get" class="filters" style="margin:0">
                <input type="hidden" name="period" value="{{ $period }}">
                <select class="select select-sm" name="salesman_id" onchange="this.form.submit()" aria-label="Salesman">
                    <option value="">All salesmen</option>
                    @foreach ($summary as $row)
                        <option value="{{ $row['id'] }}" @selected((int) request('salesman_id') === $row['id'])>{{ $row['name'] }}</option>
                    @endforeach
                </select>
                <select class="select select-sm" name="outcome" onchange="this.form.submit()" aria-label="Outcome">
                    <option value="">All outcomes</option>
                    @foreach ($outcomes as $key => $label)
                        <option value="{{ $key }}" @selected(request('outcome') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @if (request('salesman_id') || request('outcome') || request('shop_id'))
                    <a class="btn btn-ghost btn-sm" href="{{ route('visits.index', ['period' => $period]) }}#log">Clear</a>
                @endif
                <span class="muted" style="font-size:12px">{{ $visits->total() }} visit{{ $visits->total() === 1 ? '' : 's' }}</span>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Salesman</th>
                        <th>Shop</th>
                        <th>Purpose</th>
                        <th>Outcome</th>
                        <th>Order</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td style="white-space:nowrap">{{ $visit->checked_in_at?->format('d M Y H:i') }}</td>
                            <td>{{ $visit->salesman?->name }}</td>
                            <td>
                                @if ($visit->shop)
                                    <a class="link" href="{{ route('shops.show', $visit->shop) }}">{{ $visit->shop->name }}</a>
                                    <span class="muted">({{ $visit->shop->code }})</span>
                                    @if ($visit->shop->status === \App\Models\Shop::STATUS_PENDING)<span class="fa-pill mid">New · pending</span>@endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $visit->purpose ?: '—' }}</td>
                            <td><x-badge :status="$visit->outcomeLabel()" /></td>
                            <td>
                                @if ($visit->order)
                                    <a class="link" href="{{ route('orders.show', $visit->order) }}">{{ $visit->order->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="muted">
                                @if ($visit->checked_out_at)
                                    {{ $visit->checked_in_at->diffForHumans($visit->checked_out_at, true) }}
                                @else
                                    Open
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No visits {{ $period === 'all' ? 'recorded' : $periodLabel }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())
            <div style="padding:12px 16px">{{ $visits->links() }}</div>
        @endif
    </div>
@endsection
