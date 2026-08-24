@extends('layouts.app')
@section('title', 'Salesmen')
@section('content')
    <x-page-header title="Salesmen" subtitle="Field force, territories and assigned shops" action="{{ route('salesmen.create') }}" action-label="Add Salesman">
        <x-slot:description>Salesmen check in at shops, collect orders, and submit them for Super Admin audit.</x-slot:description>
        <a class="btn btn-ghost" href="{{ route('visits.index') }}">Visits</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search name, email or code">
            <select class="select" name="territory_id">
                <option value="">All territories</option>
                @foreach ($territories as $territory)
                    <option value="{{ $territory->id }}" @selected((string) request('territory_id') === (string) $territory->id)>{{ $territory->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Territory</th>
                        <th>Shops</th>
                        <th>Target</th>
                        <th>Field login</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salesmen as $row)
                        <tr>
                            <td><a class="link" href="{{ route('salesmen.show', $row) }}">{{ $row->salesmanProfile?->employee_code ?: '—' }}</a></td>
                            <td>
                                <div style="font-weight:600">{{ $row->name }}</div>
                                <div class="muted">{{ $row->phone ?: $row->email }}</div>
                            </td>
                            <td>{{ $row->salesmanProfile?->territory?->name ?: '—' }}</td>
                            <td>{{ $row->assignedShops->count() }}</td>
                            <td>{{ \App\Support\DemoData::taka($row->salesmanProfile?->monthly_target ?? 0) }}</td>
                            <td class="muted" style="font-size:12px">{{ $row->email }}</td>
                            <td><x-badge :status="$row->is_active && $row->salesmanProfile?->is_active ? 'Active' : 'Inactive'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No salesmen yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($salesmen->hasPages())
            <div style="padding:12px 16px">{{ $salesmen->links() }}</div>
        @endif
    </div>
@endsection
