@extends('layouts.app')
@section('title', 'Fulfil '.$fulfilment->order?->number)
@section('content')
    @php
        $order = $fulfilment->order;
        $simple = $order?->simpleStatus() ?? ['label' => $fulfilment->statusLabel(), 'tone' => 'gray', 'step' => 0];
        $ready = $order && $order->canDeliver();
    @endphp
    <x-page-header title="{{ $order?->number }}" subtitle="{{ $simple['label'] }} · {{ $fulfilment->warehouse?->name }}">
        <a class="btn btn-ghost" href="{{ route('fulfilment.index') }}">Queue</a>
        @if ($order)
            <a class="btn btn-ghost" href="{{ route('orders.show', $order) }}">Order</a>
        @endif
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="order-simple-steps">
        <div class="order-simple-step {{ $simple['step'] >= 2 ? 'is-done' : '' }} {{ $simple['step'] === 2 ? 'is-now' : '' }}">
            <span class="dot"></span>Approved
        </div>
        <div class="order-simple-step {{ $simple['step'] >= 3 ? 'is-done' : '' }} {{ $simple['step'] === 3 ? 'is-now' : '' }}">
            <span class="dot"></span>Delivered
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop</div><div style="font-weight:700">{{ $order?->shop?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Warehouse</div><div style="font-weight:700">{{ $fulfilment->warehouse?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($order?->total ?? 0) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Status</div><div style="font-weight:700">{{ $simple['label'] }}</div></div>
    </div>

    @if ($ready)
        @canany(['deliveries.update_status', 'orders.approve'])
            <form class="card" style="padding:16px;margin-bottom:14px" method="post" action="{{ route('fulfilment.deliver', $fulfilment) }}"
                  onsubmit="return confirm('Mark this order as delivered?')">
                @csrf
                <div style="font-weight:800;margin-bottom:4px">Deliver</div>
                <p class="muted" style="margin:0 0 10px;font-size:13px">Approve, then deliver. Collection stays on the order.</p>
                <div class="field" style="margin-bottom:10px">
                    <label class="label">Notes</label>
                    <textarea class="input" name="notes" rows="2">{{ old('notes', $fulfilment->warehouse_notes) }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Mark delivered</button>
            </form>
        @endcanany
    @elseif ($fulfilment->delivery)
        <p class="muted" style="margin:0 0 14px">
            <a class="link" href="{{ route('deliveries.show', $fulfilment->delivery) }}">Open delivery {{ $fulfilment->delivery->number }}</a>
        </p>
    @endif

    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Lines</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach ($order?->items ?? [] as $item)
                        <tr>
                            <td>{{ $item->product_sku }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
