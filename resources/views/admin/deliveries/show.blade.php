@extends('layouts.app')
@section('title', $delivery->number)
@section('content')
    <x-page-header title="{{ $delivery->number }}" subtitle="{{ $delivery->shop?->name }} · {{ $delivery->statusLabel() }}">
        <a class="btn btn-ghost" href="{{ route('deliveries.index') }}">Back</a>
        <a class="btn btn-ghost" href="{{ route('orders.show', $delivery->order) }}">Order</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Order</div><div style="font-weight:700">{{ $delivery->order?->number }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Warehouse</div><div style="font-weight:700">{{ $delivery->warehouse?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Assignee</div><div style="font-weight:700">{{ $delivery->assignee?->name ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Tracking</div><div style="font-weight:700">{{ $delivery->tracking_ref ?: '—' }}</div></div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:14px">
        <div class="muted" style="font-size:12px">Delivery address</div>
        <div style="font-weight:600;margin-top:4px">{{ $delivery->delivery_address ?: '—' }}</div>
    </div>

    @if ($delivery->status === \App\Models\Delivery::STATUS_OUT_FOR_DELIVERY)
        @can('deliveries.update_status')
            <form class="card" style="padding:16px;margin-bottom:14px" method="post" action="{{ route('deliveries.deliver', $delivery) }}">
                @csrf
                <div class="field" style="margin-bottom:10px">
                    <label class="label">Delivery notes</label>
                    <textarea class="input" name="notes" rows="2">{{ old('notes', $delivery->notes) }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Mark delivered</button>
            </form>
        @endcan
    @endif

    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Order lines</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach ($delivery->order->items as $item)
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
