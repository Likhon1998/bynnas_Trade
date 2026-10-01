@extends('field.layouts.app')
@section('title', 'My orders')
@section('heading', 'My orders')
@section('subheading', $counts['all'].' orders in total · '.$counts['waiting'].' waiting for approval')
@section('actions')
    <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.shops') }}"><i data-lucide="plus"></i> New order</a>
@endsection
@section('content')
    @php
        $tabs = ['all' => 'All', 'waiting' => 'Waiting', 'active' => 'In progress', 'delivered' => 'Delivered', 'rejected' => 'Rejected'];
    @endphp

    <div class="f-kpis">
        <a class="f-kpi tone-amber" href="{{ route('field.orders', ['status' => 'waiting']) }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="hourglass"></i></span><span class="f-kpi-label">Waiting approval</span></div>
            <div class="f-kpi-value">{{ $counts['waiting'] }}</div>
        </a>
        <a class="f-kpi tone-blue" href="{{ route('field.orders', ['status' => 'active']) }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="package"></i></span><span class="f-kpi-label">In progress</span></div>
            <div class="f-kpi-value">{{ $counts['active'] }}</div>
        </a>
        <a class="f-kpi tone-green" href="{{ route('field.orders', ['status' => 'delivered']) }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="check-circle-2"></i></span><span class="f-kpi-label">Delivered</span></div>
            <div class="f-kpi-value">{{ $counts['delivered'] }}</div>
        </a>
        <a class="f-kpi tone-rose" href="{{ route('field.orders', ['status' => 'rejected']) }}">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="circle-x"></i></span><span class="f-kpi-label">Rejected / cancelled</span></div>
            <div class="f-kpi-value">{{ $counts['rejected'] }}</div>
        </a>
    </div>

    <div class="f-filters">
        @foreach ($tabs as $key => $label)
            <a class="f-filter {{ ($filter ?? 'all') === $key ? 'active' : '' }}" href="{{ route('field.orders', $key === 'all' ? [] : ['status' => $key]) }}">{{ $label }} <span class="n">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <section class="f-panel f-desktop-only">
        <div class="f-table-wrap">
            <table class="f-table">
                <thead><tr><th>Order</th><th>Shop</th><th>Sent</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php $s = $order->simpleStatus(); @endphp
                        <tr class="is-link" onclick="window.location='{{ route('field.orders.show', $order) }}'">
                            <td><a class="strong" href="{{ route('field.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td><span class="strong">{{ $order->shop?->name }}</span><span class="sub">{{ $order->shop?->city }}</span></td>
                            <td>{{ $order->submitted_at?->format('d M Y') }}<span class="sub">{{ $order->submitted_at?->format('g:i A') }}</span></td>
                            <td class="num">{{ $order->item_count }}</td>
                            <td class="num strong">{{ \App\Support\DemoData::taka($order->total) }}</td>
                            <td><span class="f-status {{ $s['tone'] }}">{{ $s['label'] }}</span></td>
                            <td class="num"><i data-lucide="chevron-right" class="f-chev"></i></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">{{ $filter ? 'No orders in this list.' : 'No orders yet. Check in at a shop to take your first order.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="f-mobile-only">
        @forelse ($orders as $order)
            @include('field.partials.order-row', ['order' => $order])
        @empty
            <div class="f-empty">
                <i data-lucide="receipt-text"></i>
                <strong>{{ $filter ? 'No orders here' : 'No orders yet' }}</strong>
                {{ $filter ? 'Try another filter.' : 'Check in at a shop to take your first order.' }}
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div style="display:flex;gap:10px;justify-content:space-between;align-items:center;margin-top:14px">
            @if ($orders->onFirstPage())
                <span class="f-btn f-btn-ghost f-btn-sm" aria-disabled="true" style="opacity:.45">‹ Newer</span>
            @else
                <a class="f-btn f-btn-ghost f-btn-sm" href="{{ $orders->previousPageUrl() }}">‹ Newer</a>
            @endif
            <span class="f-muted f-small">Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
            @if ($orders->hasMorePages())
                <a class="f-btn f-btn-ghost f-btn-sm" href="{{ $orders->nextPageUrl() }}">Older ›</a>
            @else
                <span class="f-btn f-btn-ghost f-btn-sm" aria-disabled="true" style="opacity:.45">Older ›</span>
            @endif
        </div>
    @endif
@endsection
