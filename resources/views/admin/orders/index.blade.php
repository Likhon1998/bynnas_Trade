@extends('layouts.app')
@section('title', 'Orders')
@section('content')
    <x-page-header title="Orders" subtitle="Super Admin audit, approval and stock reservation" action="{{ route('orders.create') }}" action-label="Create Order">
        <x-slot:description>Shop portal and salesman orders both wait here before stock is reserved.</x-slot:description>
        @if ($pendingCount > 0)
            <a class="btn btn-primary" href="{{ route('orders.index', ['audit_queue' => 1]) }}">Audit queue ({{ $pendingCount }})</a>
        @endif
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Order # or shop">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['pending_audit' => 'Pending Super Admin Audit', 'approved' => 'Approved · Reserved', 'picking' => 'Picking', 'picked' => 'Picked', 'packed' => 'Packed', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value || (request('audit_queue') && $value === 'pending_audit'))>{{ $label }}</option>
                @endforeach
            </select>
            <select class="select" name="source">
                <option value="">All sources</option>
                @foreach (['salesman' => 'Salesman', 'shop_portal' => 'Shop Portal', 'admin' => 'Admin'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
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
                        <th>Source</th>
                        <th>Salesman</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a class="link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td>{{ $order->shop?->name }}</td>
                            <td class="muted">{{ ucfirst(str_replace('_', ' ', $order->source)) }}</td>
                            <td>{{ $order->salesman?->name ?: '—' }}</td>
                            <td>{{ $order->item_count }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($order->total) }}</td>
                            <td class="muted">{{ $order->submitted_at?->format('d M Y H:i') }}</td>
                            <td><x-badge :status="$order->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div style="padding:12px 16px">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
