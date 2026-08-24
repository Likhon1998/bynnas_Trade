@extends('layouts.app')
@section('title', 'Shipments')
@section('content')
    <x-page-header title="Shipments" subtitle="China origin to warehouse receipt" action="{{ route('shipments.create') }}" action-label="Add Shipment">
        <x-slot:description>Each inbound movement keeps product assignment, freight, customs and landed cost on one record.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Shipment #, tracking, origin">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['draft','booked','in_transit','arrived','received','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_',' ', ucfirst($status)) }}</option>
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
                        <th>Shipment</th>
                        <th>Origin</th>
                        <th>Carrier</th>
                        <th>Items</th>
                        <th>Landed total</th>
                        <th>ETA</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shipments as $shipment)
                        <tr>
                            <td><a class="link" href="{{ route('shipments.show', $shipment) }}">{{ $shipment->number }}</a></td>
                            <td>{{ $shipment->origin ?: '—' }}</td>
                            <td>{{ $shipment->carrier ?: '—' }}</td>
                            <td>{{ number_format($shipment->items_qty ?? 0) }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($shipment->total_landed_cost) }}</td>
                            <td class="muted">{{ $shipment->eta_at?->format('d M Y') ?: '—' }}</td>
                            <td><x-badge :status="$shipment->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shipments->hasPages())
            <div style="padding:12px 16px">{{ $shipments->links() }}</div>
        @endif
    </div>
@endsection
