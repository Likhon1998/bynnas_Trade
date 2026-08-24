@extends('layouts.app')
@section('title', 'Inventory ledger')
@section('content')
    <x-page-header title="Inventory ledger" subtitle="Receive, reserve, pick and transfer movements">
        <a class="btn btn-ghost" href="{{ route('inventory.index') }}">Back to inventory</a>
    </x-page-header>

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="warehouse_id">
                <option value="">All warehouses</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select class="select" name="type">
                <option value="">All types</option>
                @foreach (['receive','adjust','reserve','release','pick','transfer_in','transfer_out'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
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
                        <th>When</th>
                        <th>Type</th>
                        <th>Warehouse</th>
                        <th>SKU</th>
                        <th>Delta</th>
                        <th>On hand after</th>
                        <th>Reserved after</th>
                        <th>Ref</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="muted">{{ $entry->created_at?->format('d M H:i') }}</td>
                            <td>{{ $entry->type }}</td>
                            <td>{{ $entry->warehouse?->code }}</td>
                            <td>{{ $entry->product?->sku }}</td>
                            <td style="font-weight:700;color:{{ $entry->qty_delta < 0 ? '#b91c1c' : '#15803d' }}">{{ $entry->qty_delta > 0 ? '+' : '' }}{{ $entry->qty_delta }}</td>
                            <td>{{ $entry->qty_on_hand_after }}</td>
                            <td>{{ $entry->qty_reserved_after }}</td>
                            <td class="muted">{{ $entry->reference ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">No ledger entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div style="padding:12px 16px">{{ $entries->links() }}</div>
        @endif
    </div>
@endsection
