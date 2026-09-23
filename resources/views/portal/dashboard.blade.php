@extends('portal.layouts.app')
@section('title', 'Dashboard')
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>Dashboard</strong> / Welcome, {{ auth()->user()->name }}</div>
        <a class="btn btn-primary" href="{{ route('portal.products') }}">Shop catalogue</a>
    </div>
    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([
            ['Outstanding due', \App\Support\DemoData::taka($shop->outstanding_balance)],
            ['Pending audit', number_format($pendingOrders)],
            ['Price group', $shop->priceGroup?->name ?: 'Standard'],
            ['Payment terms', ($shop->payment_terms_days ?: 21).' days'],
        ] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:22px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>
    <div class="card" style="padding:18px;margin-bottom:14px">
        <div class="section-title">Your wholesale access</div>
        <p class="muted">Price group: <strong style="color:var(--text)">{{ $shop->priceGroup?->name ?: 'Standard wholesale' }}</strong>. New orders wait for Super Admin audit before stock is reserved.</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn btn-primary" href="{{ route('portal.products') }}">Browse products</a>
            <a class="btn btn-ghost" href="{{ route('portal.orders') }}">Track orders</a>
        </div>
    </div>
    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Recent orders</div>
        <div class="table-wrap">
            <table class="data">
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td><a class="link" href="{{ route('portal.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td>{{ \App\Support\DemoData::taka($order->total) }}</td>
                            <td><x-badge :status="$order->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td class="muted" style="padding:16px">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
