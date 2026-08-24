@extends('field.layouts.app')
@section('title', $order->number)
@section('heading', $order->number)
@section('content')
    <div class="list-card">
        <div style="font-weight:700">{{ $order->shop?->name }}</div>
        <div class="muted" style="font-size:12px;margin:4px 0">{{ $order->statusLabel() }} · {{ $order->submitted_at?->format('d M Y H:i') }}</div>
        <div style="font-size:22px;font-weight:800">{{ \App\Support\DemoData::taka($order->total) }}</div>
    </div>

    <div class="list-card" style="padding:8px 14px">
        @foreach ($order->items as $item)
            <div class="qty-row">
                <div>
                    <div style="font-weight:600;font-size:13px">{{ $item->product_name }}</div>
                    <div class="muted" style="font-size:11px">{{ $item->product_sku }} · ×{{ $item->quantity }}</div>
                </div>
                <div style="font-weight:700">{{ \App\Support\DemoData::taka($item->line_total) }}</div>
            </div>
        @endforeach
    </div>

    @if ($order->notes)
        <div class="list-card"><span class="muted">Notes:</span> {{ $order->notes }}</div>
    @endif

    <a class="btn btn-ghost btn-block" href="{{ route('field.dashboard') }}">Back to home</a>
@endsection
