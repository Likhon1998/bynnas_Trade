@extends('layouts.app')
@section('title', $report['title'])
@section('content')
    @php
        $period = ['from' => $from, 'to' => $to];
        $presets = [
            'Today' => [now()->toDateString(), now()->toDateString()],
            'Yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'This week' => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Last month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        ];
        $formatCell = function ($cell, ?string $type, string $column) {
            if ($type === 'money' || ($type === null && is_numeric($cell) && preg_match('/sales|profit|cost|total|value|revenue|limit|outstanding|available|freight|customs|insurance|landed|amount|base/i', $column))) {
                return \App\Support\DemoData::taka($cell);
            }
            if ($type === 'percent' || ($type === null && is_numeric($cell) && str_contains(strtolower($column), '%'))) {
                return rtrim(rtrim(number_format((float) $cell, 1), '0'), '.').'%';
            }
            if ($type === 'number' || is_int($cell) || (is_numeric($cell) && ! str_contains((string) $cell, '.'))) {
                return is_numeric($cell) ? number_format((float) $cell) : $cell;
            }
            if (is_float($cell) || (is_numeric($cell) && str_contains((string) $cell, '.'))) {
                return number_format((float) $cell, 2);
            }

            return $cell;
        };
    @endphp
    <x-page-header title="{{ $report['title'] }}" subtitle="{{ $report['description'] }}">
        <a class="btn btn-ghost" href="{{ route('reports.index', $period) }}">Back</a>
        @can('reports.export')
            <a class="btn btn-primary" href="{{ route('reports.export', ['report' => $key] + $period) }}">Download CSV</a>
        @endcan
    </x-page-header>

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" type="date" name="from" value="{{ $from }}">
            <input class="input" type="date" name="to" value="{{ $to }}">
            <button class="btn btn-primary" type="submit">Apply dates</button>
            @foreach ($presets as $label => [$presetFrom, $presetTo])
                <a class="btn btn-ghost btn-sm" href="{{ route('reports.show', ['report' => $key, 'from' => $presetFrom, 'to' => $presetTo]) }}">{{ $label }}</a>
            @endforeach
        </form>
    </div>

    @if (!empty($report['kpis']))
        <div class="order-page-summary">
            @foreach ($report['kpis'] as $kpi)
                <div>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>
                        @if (($kpi['type'] ?? '') === 'money')
                            {{ \App\Support\DemoData::taka($kpi['value']) }}
                        @else
                            {{ number_format((float) $kpi['value']) }}
                        @endif
                    </strong>
                </div>
            @endforeach
        </div>
    @endif

    @php
        $tables = [];
        if (($report['columns'] ?? []) !== [] || ($report['rows'] ?? []) !== []) {
            $tables[] = $report;
        }
        foreach ($report['sections'] ?? [] as $section) {
            $tables[] = $section;
        }
    @endphp

    @forelse ($tables as $table)
        <div class="card" style="margin-bottom:14px">
            @if (!empty($table['title']) && $table !== $report)
                <div class="order-page-card-head" style="padding:14px 16px 0">
                    <strong>{{ $table['title'] }}</strong>
                </div>
            @endif
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            @foreach ($table['columns'] as $col)
                                @php $type = $table['column_types'][$loop->index] ?? null; @endphp
                                <th class="{{ in_array($type, ['money', 'number', 'percent'], true) ? 'num' : '' }}">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($table['rows'] as $row)
                            <tr>
                                @foreach ($row as $i => $cell)
                                    @php $type = $table['column_types'][$i] ?? null; @endphp
                                    <td class="{{ in_array($type, ['money', 'number', 'percent'], true) ? 'num' : '' }}">
                                        {{ $formatCell($cell, $type, $table['columns'][$i] ?? '') }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(1, count($table['columns'])) }}" class="empty">No rows for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty" style="padding:24px;text-align:center">No rows for this period.</div>
        </div>
    @endforelse
@endsection
