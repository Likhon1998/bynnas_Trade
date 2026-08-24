@extends('field.layouts.app')
@section('title', 'Orders')
@section('heading', 'My orders')
@section('content')
    @forelse ($orders as $order)
        <a class="list-card" href="{{ route('field.orders.show', $order) }}" style="display:block;text-decoration:none;color:inherit">
            <div style="display:flex;justify-content:space-between;gap:8px">
                <strong>{{ $order->number }}</strong>
                <span style="font-weight:700">{{ \App\Support\DemoData::taka($order->total) }}</span>
            </div>
            <div class="muted" style="font-size:12px;margin-top:4px">{{ $order->shop?->name }} · {{ $order->submitted_at?->format('d M H:i') }}</div>
            <div style="margin-top:6px"><x-badge :status="$order->statusLabel()" /></div>
        </a>
    @empty
        <div class="list-card muted">No orders yet.</div>
    @endforelse
    @if ($orders->hasPages())
        <div style="padding:8px 0">{{ $orders->links() }}</div>
    @endif
@endsection
