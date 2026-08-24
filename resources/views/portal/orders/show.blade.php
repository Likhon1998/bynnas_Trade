@extends('portal.layouts.app')
@section('title', $order->number)
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>{{ $order->number }}</strong> / {{ $order->statusLabel() }}</div>
        <a class="btn btn-ghost" href="{{ route('portal.orders') }}">Back</a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:800">{{ \App\Support\DemoData::taka($order->total) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Salesman</div><div style="font-weight:700">{{ $order->salesman?->name ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Submitted</div><div style="font-weight:700">{{ $order->submitted_at?->format('d M Y H:i') }}</div></div>
    </div>

    @if ($order->isPendingAudit())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fff7ed;color:#9a3412">Waiting for Super Admin audit. Stock is not reserved yet.</div>
    @elseif ($order->stock_reserved)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">Approved — stock reserved. Warehouse fulfilment comes next.</div>
    @elseif ($order->status === \App\Models\Order::STATUS_REJECTED)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">Rejected: {{ $order->rejection_reason }}</div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product_sku }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ \App\Support\DemoData::taka($item->unit_price) }}</td>
                            <td style="font-weight:600">{{ \App\Support\DemoData::taka($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
