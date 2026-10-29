@extends('field.layouts.app')
@php
    $periodLabel = $singleDay
        ? \Illuminate\Support\Carbon::parse($from)->isToday()
            ? 'Today'
            : \Illuminate\Support\Carbon::parse($from)->format('d M Y')
        : \Illuminate\Support\Carbon::parse($from)->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($to)->format('d M Y');
    $chips = [
        ['label' => 'Today', 'from' => today()->toDateString(), 'to' => today()->toDateString()],
        ['label' => 'Yesterday', 'from' => today()->subDay()->toDateString(), 'to' => today()->subDay()->toDateString()],
        ['label' => 'Last 7 days', 'from' => today()->subDays(6)->toDateString(), 'to' => today()->toDateString()],
        ['label' => 'Last 30 days', 'from' => today()->subDays(29)->toDateString(), 'to' => today()->toDateString()],
        ['label' => 'This month', 'from' => now()->startOfMonth()->toDateString(), 'to' => today()->toDateString()],
    ];
@endphp
@section('title', 'Visit log')
@section('heading', 'Visit log')
@section('subheading', $shopTotal.' shops · '.$visitTotal.' visits · '.$periodLabel)
@section('back', route('field.dashboard'))
@section('actions')
    <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.shops') }}"><i data-lucide="store"></i> Shops</a>
@endsection
@section('content')
    <form method="get" action="{{ route('field.visits') }}" class="f-datebar">
        <label class="f-datebar-field">
            <span>From</span>
            <input class="f-input" type="date" name="from" value="{{ $from }}" max="{{ today()->toDateString() }}" aria-label="From date">
        </label>
        <label class="f-datebar-field">
            <span>To</span>
            <input class="f-input" type="date" name="to" value="{{ $to }}" max="{{ today()->toDateString() }}" aria-label="To date">
        </label>
        <button class="f-btn f-btn-primary" type="submit"><i data-lucide="search"></i> Search</button>
        @if ($filtered)
            <a class="f-btn f-btn-ghost" href="{{ route('field.visits') }}">Clear</a>
        @endif
    </form>
    <div class="f-filters" style="margin:0 0 12px;padding:0">
        @foreach ($chips as $chip)
            <a class="f-filter {{ $from === $chip['from'] && $to === $chip['to'] ? 'active' : '' }}"
               href="{{ route('field.visits', ['from' => $chip['from'], 'to' => $chip['to']]) }}">{{ $chip['label'] }}</a>
        @endforeach
    </div>

    <div class="f-kpis">
        <div class="f-kpi tone-blue">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="map-pin"></i></span><span class="f-kpi-label">Shops</span></div>
            <div class="f-kpi-value">{{ $shopTotal }}</div>
            <div class="f-kpi-meta">{{ $periodLabel }}</div>
        </div>
        <div class="f-kpi tone-violet">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="clipboard-list"></i></span><span class="f-kpi-label">Visits</span></div>
            <div class="f-kpi-value">{{ $visitTotal }}</div>
            <div class="f-kpi-meta">Check-ins in this range</div>
        </div>
        <div class="f-kpi tone-green">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="calendar"></i></span><span class="f-kpi-label">Days</span></div>
            <div class="f-kpi-value">{{ $days->filter(fn ($day) => $day['visits'] > 0)->count() }}</div>
            <div class="f-kpi-meta">Days with a visit</div>
        </div>
        <a class="f-kpi tone-amber" href="{{ route('field.shops') }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="store"></i></span><span class="f-kpi-label">Assigned shops</span></div>
            <div class="f-kpi-value">Visit</div>
            <div class="f-kpi-meta">Check in, then leave with a note</div>
        </a>
    </div>

    @forelse ($days as $key => $day)
        <section class="f-panel f-visit-day {{ $day['date']->isToday() ? 'is-today' : '' }}">
            <div class="f-panel-head">
                <h2>
                    <span class="f-ico tone-blue"><i data-lucide="calendar"></i></span>
                    {{ $day['date']->isToday() ? 'Today' : ($day['date']->isYesterday() ? 'Yesterday' : $day['date']->format('D, d M Y')) }}
                </h2>
                <span class="f-chip {{ $day['shops'] ? 'green' : '' }}">{{ $day['shops'] }} {{ \Illuminate\Support\Str::plural('shop', $day['shops']) }} · {{ $day['visits'] }} {{ \Illuminate\Support\Str::plural('visit', $day['visits']) }}</span>
            </div>
            @if ($day['rows']->isEmpty())
                <div class="f-panel-body">
                    <p class="f-muted f-small" style="margin:0">No shops visited this day.</p>
                </div>
            @else
                <div class="f-table-wrap">
                    <table class="f-table">
                        <thead>
                            <tr>
                                <th>Shop</th>
                                <th>In</th>
                                <th>Out</th>
                                <th>Talked</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($day['rows'] as $visit)
                                <tr>
                                    <td>
                                        <span class="strong">{{ $visit->shop?->name }}</span>
                                        <span class="sub">{{ $visit->isOpen() ? 'Still there' : $visit->outcomeLabel() }}</span>
                                    </td>
                                    <td class="f-muted">{{ $visit->checked_in_at?->format('g:i A') }}</td>
                                    <td class="f-muted">{{ $visit->checked_out_at?->format('g:i A') ?: '—' }}</td>
                                    <td>{{ $visit->notes ? \Illuminate\Support\Str::limit($visit->notes, 70) : '—' }}</td>
                                    <td class="num">
                                        <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.visit.show', $visit) }}">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @empty
        <div class="f-empty">
            <i data-lucide="calendar-search"></i>
            <strong>No visits in this date range</strong>
            Try another date, or visit an assigned shop and leave with a note.
        </div>
    @endforelse
@endsection
