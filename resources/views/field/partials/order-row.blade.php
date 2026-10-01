@php $s = $order->simpleStatus(); @endphp
<a class="f-order" href="{{ route('field.orders.show', $order) }}">
    <div class="f-order-main">
        <div class="f-order-top">
            <strong>{{ $order->shop?->name ?? $order->number }}</strong>
            <span class="f-order-amt">{{ \App\Support\DemoData::taka($order->total) }}</span>
        </div>
        <div class="f-order-sub">
            <span>{{ $order->number }} · {{ $order->submitted_at?->isToday() ? 'Today '.$order->submitted_at->format('g:i A') : $order->submitted_at?->format('d M') }}</span>
            <span class="f-status {{ $s['tone'] }}">{{ $s['label'] }}</span>
        </div>
    </div>
    <i data-lucide="chevron-right" class="f-chev"></i>
</a>
