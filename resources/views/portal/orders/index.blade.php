@extends('portal.layouts.app')
@section('title', 'My orders')
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>Orders</strong> / Track audit status</div>
        <a class="btn btn-primary" href="{{ route('portal.orders.create') }}">Place order</a>
    </div>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a class="link" href="{{ route('portal.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td>{{ $order->item_count }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($order->total) }}</td>
                            <td class="muted">{{ $order->submitted_at?->format('d M Y H:i') }}</td>
                            <td><x-badge :status="$order->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div style="padding:14px">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
