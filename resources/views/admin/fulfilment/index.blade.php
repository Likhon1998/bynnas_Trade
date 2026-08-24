@extends('layouts.app')
@section('title', 'Fulfilment')
@section('content')
    <x-page-header title="Fulfilment queue" subtitle="Pick → pack → dispatch approved orders">
        <a class="btn btn-ghost" href="{{ route('deliveries.index') }}">Deliveries</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="status">
                <option value="">Open queue</option>
                @foreach (['awaiting_pick'=>'Awaiting pick','picking'=>'Picking','picked'=>'Picked','packed'=>'Packed','dispatched'=>'Dispatched','delivered'=>'Delivered'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
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
                        <th>Order</th>
                        <th>Shop</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                        <th>Picker</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queue as $row)
                        <tr>
                            <td><a class="link" href="{{ route('fulfilment.show', $row) }}">{{ $row->order?->number }}</a></td>
                            <td>{{ $row->order?->shop?->name }}</td>
                            <td>{{ $row->warehouse?->code }}</td>
                            <td><x-badge :status="$row->statusLabel()" /></td>
                            <td>{{ $row->picker?->name ?: '—' }}</td>
                            <td><a class="btn btn-ghost" href="{{ route('fulfilment.show', $row) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Queue empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($queue->hasPages())
            <div style="padding:12px 16px">{{ $queue->links() }}</div>
        @endif
    </div>
@endsection
