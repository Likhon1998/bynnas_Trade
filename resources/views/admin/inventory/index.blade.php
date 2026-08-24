@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
    <x-page-header title="Inventory" subtitle="Live stock across warehouses" action="{{ route('inventory.receive') }}" action-label="Receive Stock">
        <x-slot:description>On-hand, reserved and available stay in sync with approvals and warehouse picks.</x-slot:description>
        <a class="btn btn-ghost" href="{{ route('inventory.ledger') }}">Ledger</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="warehouse_id">
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search SKU or product">
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>On hand</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th>Warehouse</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        <tr>
                            <td style="font-weight:700">{{ $stock->product?->sku }}</td>
                            <td>{{ $stock->product?->name }}</td>
                            <td>{{ number_format($stock->qty_on_hand) }}</td>
                            <td>{{ number_format($stock->qty_reserved) }}</td>
                            <td style="font-weight:700">{{ number_format($stock->available()) }}</td>
                            <td>{{ $stock->warehouse?->code }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">No stock rows yet. Receive stock or run Phase 5 seed.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stocks->hasPages())
            <div style="padding:12px 16px">{{ $stocks->links() }}</div>
        @endif
    </div>
@endsection
