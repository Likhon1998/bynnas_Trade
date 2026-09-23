@extends('portal.layouts.app')
@section('title', $order->number)
@section('content')
    <div class="toolbar">
        <div class="page-kicker"><strong>{{ $order->number }}</strong> / {{ $order->statusLabel() }}</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if ($order->canPartnerEdit())
                <a class="btn btn-primary" href="{{ route('portal.orders.edit', $order) }}">Edit order</a>
                <form method="post" action="{{ route('portal.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order before approval?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-ghost" type="submit" style="color:#b91c1c;border-color:#fecaca">Delete order</button>
                </form>
            @endif
            <a class="btn btn-ghost" href="{{ route('portal.orders') }}">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:800">{{ \App\Support\DemoData::taka($order->total) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Salesman</div><div style="font-weight:700">{{ $order->salesman?->name ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Submitted</div><div style="font-weight:700">{{ $order->submitted_at?->format('d M Y H:i') }}</div></div>
    </div>

    @if ($order->isAwaitingAdvance())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fffbeb;color:#92400e">
            <strong>Advance payment required</strong> before this order can be approved.
            <div style="margin-top:6px;font-weight:800;font-size:18px">{{ \App\Support\DemoData::taka($order->advance_amount) }}</div>
            @if ($order->advanceInvoice)
                <div style="margin-top:4px;font-size:13px">
                    Invoice {{ $order->advanceInvoice->number }}
                    · Balance {{ \App\Support\DemoData::taka($order->advanceInvoice->balance) }}
                    @if ($order->isAdvancePaid())
                        · <span style="color:#15803d;font-weight:700">Paid — waiting for admin approval</span>
                    @else
                        · Please pay this advance (cash / bank / mobile) and inform admin for verification.
                    @endif
                </div>
            @endif
        </div>
    @elseif ($order->isPendingAudit())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fff7ed;color:#9a3412">
            Waiting for Super Admin audit. Stock is not reserved yet.
            @if ($order->canPartnerEdit())
                You can still <a class="link" href="{{ route('portal.orders.edit', $order) }}">edit</a> or delete this order before approval.
            @endif
        </div>
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
        @if ($order->notes)
            <div style="padding:14px;border-top:1px solid var(--line)"><span class="muted">Notes:</span> {{ $order->notes }}</div>
        @endif
    </div>
@endsection
