@extends('field.layouts.app')
@section('title', 'Deliveries')
@section('heading', 'Deliveries')
@section('subheading', 'Products delivered to your shops')
@section('content')
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Delivery</th><th>Shop</th><th>Order</th><th>Status</th><th>Assignee</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td>{{ $delivery->number }}</td>
                            <td>{{ $delivery->shop?->name }}</td>
                            <td><a class="link" href="{{ route('field.orders.show', $delivery->order) }}">{{ $delivery->order?->number }}</a></td>
                            <td>{{ $delivery->statusLabel() }}</td>
                            <td>{{ $delivery->assignee?->name ?: '—' }}</td>
                            <td>{{ ($delivery->delivered_at ?? $delivery->dispatched_at)?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:16px">No deliveries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px">{{ $deliveries->links() }}</div>
    </div>
@endsection
