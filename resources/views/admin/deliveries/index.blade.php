@extends('layouts.app')
@section('title', 'Deliveries')
@section('content')
    <x-page-header title="Deliveries" subtitle="Out for delivery and completed drops">
        <a class="btn btn-ghost" href="{{ route('fulfilment.index') }}">Fulfilment queue</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'failed' => 'Failed'] as $value => $label)
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
                        <th>Delivery</th>
                        <th>Order</th>
                        <th>Shop</th>
                        <th>Assignee</th>
                        <th>Dispatched</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td><a class="link" href="{{ route('deliveries.show', $delivery) }}">{{ $delivery->number }}</a></td>
                            <td>{{ $delivery->order?->number }}</td>
                            <td>{{ $delivery->shop?->name }}</td>
                            <td>{{ $delivery->assignee?->name ?: '—' }}</td>
                            <td class="muted">{{ $delivery->dispatched_at?->format('d M H:i') }}</td>
                            <td><x-badge :status="$delivery->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">No deliveries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($deliveries->hasPages())
            <div style="padding:12px 16px">{{ $deliveries->links() }}</div>
        @endif
    </div>
@endsection
