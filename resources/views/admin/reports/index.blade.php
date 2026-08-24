@extends('layouts.app')
@section('title', 'Reports')
@section('content')
    <x-page-header title="Reports" subtitle="Export-ready operational packs">
        <x-slot:description>Each pack pulls live data for the selected period. Open a report to preview or download CSV.</x-slot:description>
    </x-page-header>

    <div class="grid-4">
        @foreach ($catalog as $key => $report)
            <div class="card" style="padding:18px">
                <div class="section-title">{{ $report['title'] }}</div>
                <p class="muted">{{ $report['description'] }}</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a class="btn btn-primary" href="{{ route('reports.show', $key) }}">Open</a>
                    @can('reports.export')
                        <a class="btn btn-ghost" href="{{ route('reports.export', $key) }}">CSV</a>
                    @else
                        <a class="btn btn-ghost" href="{{ route('reports.export', $key) }}">CSV</a>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>
@endsection
