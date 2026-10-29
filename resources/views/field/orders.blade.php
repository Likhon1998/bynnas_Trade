@extends('field.layouts.app')
@section('title', 'My orders')
@section('heading', 'My orders')
@section('subheading', $counts['all'].' orders in total · '.$counts['waiting'].' waiting for approval')
@section('actions')
    <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.orders.create') }}"><i data-lucide="plus"></i> New order</a>
@endsection
@section('content')
    @php
        $tabs = ['all' => 'All', 'waiting' => 'Waiting', 'active' => 'In progress', 'delivered' => 'Delivered', 'rejected' => 'Rejected'];
    @endphp

    <a class="f-btn f-btn-primary f-btn-block f-mobile-only" style="margin-bottom:12px" href="{{ route('field.orders.create') }}">
        <i data-lucide="plus"></i> New order
    </a>

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

    <section class="f-panel">
        <div class="f-table-wrap">
            <table class="f-table f-table-orders">
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th class="f-table-orders-wide">Order</th>
                        <th class="f-table-orders-wide">Date</th>
                        <th class="num">Total</th>
                        <th class="num">Baki</th>
                        <th class="status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @include('field.partials.order-table-row', ['order' => $order])
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                @if ($filter)
                                    No orders in this list.
                                @else
                                    No orders yet.
                                    <div style="margin-top:10px">
                                        <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.orders.create') }}"><i data-lucide="plus"></i> New order</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

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
