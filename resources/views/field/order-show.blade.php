@extends('field.layouts.app')
@php
    $s = $order->simpleStatus();
    $phone = preg_replace('/[^0-9+]/', '', (string) $order->shop?->phone);
    $steps = [1 => 'Sent', 2 => 'Approved', 3 => 'Packed', 4 => 'On the way', 5 => 'Delivered'];
@endphp
@section('title', 'Order '.$order->number)
@section('heading', 'Order '.$order->number)
@section('subheading', $order->shop?->name)
@section('back', route('field.orders'))
@section('content')
    <div class="f-split">
    <div>
    <div class="f-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
            <div>
                <div class="f-muted f-small">Order total</div>
                <div style="font-size:22px;font-weight:800;color:var(--f-ink);letter-spacing:-.02em">{{ \App\Support\DemoData::taka($order->total) }}</div>
                <div class="f-muted f-small">{{ $order->item_count }} pcs · sent {{ $order->submitted_at?->format('d M, g:i A') }}</div>
            </div>
            <span class="f-status {{ $s['tone'] }}">{{ $s['label'] }}</span>
        </div>

        @if ($s['step'] > 0)
            <div class="f-steps" style="--done: {{ ($s['step'] - 1) / 4 * 100 }}%">
                <div class="bar"></div>
                @foreach ($steps as $n => $label)
                    <div class="f-step {{ $n < $s['step'] ? 'done' : ($n === $s['step'] ? 'now' : '') }}">
                        <span class="dot"></span>{{ $label }}
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($order->status === \App\Models\Order::STATUS_REJECTED && $order->rejection_reason)
        <div class="f-flash err" style="margin:0 0 12px">
            <i data-lucide="circle-x"></i>
            <span><strong>Rejected:</strong> {{ $order->rejection_reason }}</span>
        </div>
    @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED && $order->cancellation_reason)
        <div class="f-flash err" style="margin:0 0 12px">
            <i data-lucide="ban"></i>
            <span><strong>Cancelled:</strong> {{ $order->cancellation_reason }}</span>
        </div>
    @elseif ($order->status === \App\Models\Order::STATUS_AWAITING_ADVANCE && ! $order->advance_paid_at)
        <div class="f-warn" style="margin:0 0 12px">
            The shop needs to pay an advance of <b>{{ \App\Support\DemoData::taka($order->advance_amount) }}</b> before this order is approved. Remind the owner when you call.
        </div>
    @endif

    <div class="f-section"><h2>Items</h2></div>
    <div class="f-card" style="padding:4px 14px">
        @foreach ($order->items as $item)
            <div class="f-line-item">
                <span>{{ $item->product_name }} <span class="f-muted">× {{ $item->quantity }}</span></span>
                <span>{{ \App\Support\DemoData::taka($item->line_total) }}</span>
            </div>
        @endforeach
        @if ((float) $order->discount_total > 0)
            <div class="f-line-item"><span class="f-muted">Discount</span><span>− {{ \App\Support\DemoData::taka($order->discount_total) }}</span></div>
        @endif
        <div class="f-total-row"><span>Total</span><strong>{{ \App\Support\DemoData::taka($order->total) }}</strong></div>
    </div>
    </div>

    <aside>
        <div class="f-section f-desktop-only"><h2>Shop</h2></div>
        <div class="f-card f-desktop-only">
            <div class="f-kv"><span>Shop</span><span>{{ $order->shop?->name }}</span></div>
            @if ($order->shop?->owner_name)<div class="f-kv"><span>Owner</span><span>{{ $order->shop->owner_name }}</span></div>@endif
            @if ($order->shop?->phone)<div class="f-kv"><span>Phone</span><span>{{ $order->shop->phone }}</span></div>@endif
            @if ($order->shop?->city)<div class="f-kv"><span>Area</span><span>{{ $order->shop->city }}</span></div>@endif
        </div>

        @if ($order->notes)
            <div class="f-section"><h2>Your note</h2></div>
            <div class="f-card">{{ $order->notes }}</div>
        @endif

        <div class="f-shop-actions" style="margin-top:16px">
            @if ($phone)
                <a class="f-btn f-btn-soft f-btn-lg" style="flex:1" href="tel:{{ $phone }}"><i data-lucide="phone"></i> Call shop</a>
            @endif
            <a class="f-btn f-btn-ghost f-btn-lg" style="flex:1" href="{{ route('field.shops') }}"><i data-lucide="store"></i> My shops</a>
        </div>
    </aside>
    </div>
@endsection
