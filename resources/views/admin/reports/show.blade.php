@extends('layouts.app')
@section('title', $report['title'])
@section('content')
    <x-page-header title="{{ $report['title'] }}" subtitle="{{ $report['description'] }}">
        <a class="btn btn-ghost" href="{{ route('reports.index') }}">Back</a>
        <a class="btn btn-primary" href="{{ route('reports.export', ['report' => $key, 'from' => $from, 'to' => $to]) }}">Download CSV</a>
    </x-page-header>

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" type="date" name="from" value="{{ $from }}">
            <input class="input" type="date" name="to" value="{{ $to }}">
            <button class="btn btn-ghost" type="submit">Apply period</button>
        </form>
        @if (!empty($report['meta']))
            <div class="muted" style="margin-top:10px;font-size:13px">
                @foreach ($report['meta'] as $k => $v)
                    <span style="margin-right:14px">{{ ucfirst(str_replace('_', ' ', $k)) }}: {{ is_float($v) ? \App\Support\DemoData::taka($v) : $v }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        @foreach ($report['columns'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report['columns']) }}" class="muted" style="padding:24px;text-align:center">No rows for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
