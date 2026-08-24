@extends('layouts.app')
@section('title', 'Fulfil '.$fulfilment->order?->number)
@section('content')
    <x-page-header title="{{ $fulfilment->order?->number }}" subtitle="{{ $fulfilment->statusLabel() }} · {{ $fulfilment->warehouse?->name }}">
        <a class="btn btn-ghost" href="{{ route('fulfilment.index') }}">Queue</a>
        <a class="btn btn-ghost" href="{{ route('orders.show', $fulfilment->order) }}">Order</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop</div><div style="font-weight:700">{{ $fulfilment->order?->shop?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Warehouse</div><div style="font-weight:700">{{ $fulfilment->warehouse?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($fulfilment->order?->total ?? 0) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Status</div><div style="font-weight:700">{{ $fulfilment->statusLabel() }}</div></div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:14px">
        <div style="font-weight:700;margin-bottom:10px">Warehouse actions</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            @if ($fulfilment->status === \App\Models\OrderFulfilment::STATUS_AWAITING_PICK)
                @can('fulfilment.pick')
                    <form method="post" action="{{ route('fulfilment.start-pick', $fulfilment) }}">@csrf
                        <button class="btn btn-ghost" type="submit">Start pick</button>
                    </form>
                    <form method="post" action="{{ route('fulfilment.complete-pick', $fulfilment) }}">@csrf
                        <button class="btn btn-primary" type="submit">Pick & deduct stock</button>
                    </form>
                @endcan
            @endif

            @if ($fulfilment->status === \App\Models\OrderFulfilment::STATUS_PICKING)
                @can('fulfilment.pick')
                    <form method="post" action="{{ route('fulfilment.complete-pick', $fulfilment) }}">@csrf
                        <button class="btn btn-primary" type="submit">Complete pick</button>
                    </form>
                @endcan
            @endif

            @if ($fulfilment->status === \App\Models\OrderFulfilment::STATUS_PICKED)
                @can('fulfilment.pack')
                    <form method="post" action="{{ route('fulfilment.pack', $fulfilment) }}">@csrf
                        <button class="btn btn-primary" type="submit">Mark packed</button>
                    </form>
                @endcan
            @endif

            @if ($fulfilment->status === \App\Models\OrderFulfilment::STATUS_PACKED)
                @can('fulfilment.dispatch')
                    <form method="post" action="{{ route('fulfilment.dispatch', $fulfilment) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
                        @csrf
                        <div class="field" style="margin:0">
                            <label class="label">Driver / staff</label>
                            <select class="select" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin:0">
                            <label class="label">Tracking ref</label>
                            <input class="input" name="tracking_ref" placeholder="Optional">
                        </div>
                        <button class="btn btn-primary" type="submit">Dispatch</button>
                    </form>
                @endcan
            @endif

            @if ($fulfilment->delivery)
                <a class="btn btn-ghost" href="{{ route('deliveries.show', $fulfilment->delivery) }}">Open delivery {{ $fulfilment->delivery->number }}</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Lines</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Still reserved</th></tr></thead>
                <tbody>
                    @foreach ($fulfilment->order->items as $item)
                        <tr>
                            <td>{{ $item->product_sku }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->reserved_quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
