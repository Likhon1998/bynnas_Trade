@php
    $s = $order->simpleStatus();
    $due = (float) $order->collectibleBalance();
@endphp
<a class="f-order" href="{{ route('field.orders.show', $order) }}{{ $due > 0.009 ? '#collect-bill' : '' }}">
    <div class="f-order-main">
        <div class="f-order-top">
            <strong>{{ $order->shop?->name ?? $order->number }}</strong>
            <span class="f-order-amt">{{ \App\Support\DemoData::taka($order->total) }}</span>
        </div>
        <div class="f-order-sub">
            <span>{{ $order->number }} · {{ $order->submitted_at?->isToday() ? 'Today '.$order->submitted_at->format('g:i A') : $order->submitted_at?->format('d M') }}</span>
            <span class="f-status {{ $s['tone'] }}">{{ $s['label'] }}</span>
        </div>
        @if ($due > 0.009)
            <div class="f-collect-pill">Baki {{ \App\Support\DemoData::taka($due) }}</div>
        @endif
    </div>
    <i data-lucide="chevron-right" class="f-chev"></i>
</a>
