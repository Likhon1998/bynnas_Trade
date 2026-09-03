@extends('layouts.app')
@section('title', $order->number)
@section('content')
    <x-page-header title="{{ $order->number }}" subtitle="{{ $order->shop?->name }} · {{ $order->statusLabel() }}">
        <a class="btn btn-ghost" href="{{ route('orders.index') }}">Back</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
            <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop</div><div style="font-weight:700">{{ $order->shop?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Salesman</div><div style="font-weight:700">{{ $order->salesman?->name ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Source</div><div style="font-weight:700">{{ ucfirst(str_replace('_', ' ', $order->source)) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($order->total) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop credit available</div><div style="font-weight:700;color:{{ $snapshot['credit_ok'] ? '#15803d' : '#b91c1c' }}">{{ \App\Support\DemoData::taka($snapshot['credit_available']) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Stock check</div><div style="font-weight:700;color:{{ $snapshot['stock_ok'] ? '#15803d' : '#b91c1c' }}">{{ $snapshot['stock_ok'] ? 'OK' : 'Shortfall' }}</div></div>
    </div>

    @if ($order->fulfilment)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
            <div>
                <div style="font-weight:700">Warehouse fulfilment · {{ $order->fulfilment->statusLabel() }}</div>
                <div class="muted" style="font-size:12px">{{ $order->warehouse?->name ?: $order->fulfilment->warehouse?->name }}</div>
            </div>
            <a class="btn btn-primary" href="{{ route('fulfilment.show', $order->fulfilment) }}">Open fulfilment</a>
        </div>
    @elseif ($order->isApproved())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fff7ed;color:#9a3412">
            Approved — awaiting warehouse queue. Run Phase 5 seed if fulfilment is missing.
        </div>
    @endif

    @if ($order->invoice)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
            <div>
                <div style="font-weight:700">Invoice {{ $order->invoice->number }} · {{ $order->invoice->statusLabel() }}</div>
                <div class="muted" style="font-size:12px">Balance {{ \App\Support\DemoData::taka($order->invoice->balance) }} of {{ \App\Support\DemoData::taka($order->invoice->total) }}</div>
            </div>
            <a class="btn btn-primary" href="{{ route('invoices.show', $order->invoice) }}">Open invoice</a>
        </div>
    @elseif (in_array($order->status, ['delivered', 'dispatched', 'packed', 'picked', 'approved'], true))
        @can('invoices.create')
            <div class="card" style="padding:12px 16px;margin-bottom:14px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
                <div>
                    <div style="font-weight:700">No invoice yet</div>
                    <div class="muted" style="font-size:12px">Delivered orders auto-invoice; you can also raise one manually.</div>
                </div>
                <form method="post" action="{{ route('orders.invoice', $order) }}">@csrf<button class="btn btn-primary" type="submit">Raise invoice</button></form>
            </div>
        @endcan
    @endif

    @if ($order->isPendingAudit())
        <div class="card" style="padding:16px;margin-bottom:14px;border:1px solid #fed7aa;background:#fffbeb">
            <div style="font-weight:700;margin-bottom:8px;color:#9a3412">Super Admin audit</div>
            <p class="muted" style="margin:0 0 12px;font-size:13px">Approve only after credit and available stock look correct. Approval reserves stock immediately.</p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <form method="post" action="{{ route('orders.approve', $order) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px">
                        <label class="label">Audit notes (optional)</label>
                        <textarea class="input" name="audit_notes" rows="2" placeholder="Credit / stock notes">{{ old('audit_notes') }}</textarea>
                    </div>
                    @unless ($snapshot['credit_ok'])
                        <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:10px;color:#b91c1c">
                            <input type="checkbox" name="credit_override" value="1"> Override credit limit and approve anyway
                        </label>
                    @endunless
                    <button class="btn btn-primary" type="submit" @disabled(! $snapshot['stock_ok'])>Approve & reserve stock</button>
                    @unless ($snapshot['stock_ok'])
                        <div class="muted" style="font-size:12px;margin-top:8px">Fix stock shortfalls before approving.</div>
                    @endunless
                </form>

                <form method="post" action="{{ route('orders.reject', $order) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px">
                        <label class="label">Rejection reason</label>
                        <textarea class="input" name="rejection_reason" rows="2" required placeholder="Why this order is rejected">{{ old('rejection_reason') }}</textarea>
                    </div>
                    <button class="btn btn-ghost" type="submit" style="color:#b91c1c;border-color:#fecaca">Reject order</button>
                </form>
            </div>

            @can('delete', $order)
                <form method="post" action="{{ route('orders.destroy', $order) }}" style="margin-top:14px;padding-top:14px;border-top:1px dashed #fcd34d;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap" onsubmit="return confirm('Delete this order before approval?')">
                    @csrf
                    @method('DELETE')
                    <div class="muted" style="font-size:12px">Or delete this order entirely before approval.</div>
                    <button class="btn btn-ghost" type="submit" style="color:#b91c1c;border-color:#fecaca">Delete order</button>
                </form>
            @endcan
        </div>
    @endif

    @if ($order->isApproved() || $order->isPendingAudit())
        @can('cancel', $order)
            <div class="card" style="padding:14px;margin-bottom:14px">
                <form method="post" action="{{ route('orders.cancel', $order) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                    @csrf
                    <div class="field" style="flex:1;min-width:220px;margin:0">
                        <label class="label">Cancel reason</label>
                        <input class="input" name="cancellation_reason" required placeholder="Reason for cancellation">
                    </div>
                    <button class="btn btn-ghost" type="submit">Cancel order{{ $order->isApproved() ? ' & release stock' : '' }}</button>
                </form>
            </div>
        @endcan
    @endif

    @if ($order->rejection_reason)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">Rejected: {{ $order->rejection_reason }}</div>
    @endif
    @if ($order->cancellation_reason)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#f3f4f6">Cancelled: {{ $order->cancellation_reason }}</div>
    @endif
    @if ($order->stock_reserved)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">
            Stock reserved {{ $order->stock_reserved_at?->format('d M Y H:i') }} by {{ $order->auditor?->name }}. Ready for warehouse picking (Phase 5).
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:14px">
        <div class="card">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Order lines</div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Available now</th>
                            <th>Reserved</th>
                            <th>Unit</th>
                            <th>Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            @php
                                $avail = $item->product?->availableStock() ?? ($item->available_at_audit ?? 0);
                                $ok = $avail >= $item->quantity || $item->reserved_quantity > 0;
                            @endphp
                            <tr>
                                <td>{{ $item->product_sku }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td style="color:{{ $ok ? 'inherit' : '#b91c1c' }};font-weight:{{ $ok ? '400' : '700' }}">{{ number_format($avail) }}</td>
                                <td>{{ $item->reserved_quantity }}</td>
                                <td>{{ \App\Support\DemoData::taka($item->unit_price) }}</td>
                                <td style="font-weight:600">{{ \App\Support\DemoData::taka($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($order->notes)
                <div style="padding:14px;border-top:1px solid #eee"><span class="muted">Notes:</span> {{ $order->notes }}</div>
            @endif
        </div>

        <div class="card" style="padding:16px">
            <div style="font-weight:700;margin-bottom:10px">Fulfilment pipeline</div>
            <ol class="muted" style="padding-left:18px;line-height:1.8;margin:0;font-size:13px">
                <li>Submitted for Super Admin audit</li>
                <li style="{{ in_array($order->status, ['approved','picking','picked','packed','dispatched','delivered','rejected','cancelled'], true) ? 'color:#15803d;font-weight:600' : '' }}">Credit + stock checked</li>
                <li style="{{ $order->stock_reserved || in_array($order->status, ['picking','picked','packed','dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Stock reserved</li>
                <li style="{{ in_array($order->status, ['picked','packed','dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Warehouse pick</li>
                <li style="{{ in_array($order->status, ['packed','dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Pack</li>
                <li style="{{ in_array($order->status, ['dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Dispatch</li>
                <li style="{{ $order->status === 'delivered' ? 'color:#15803d;font-weight:600' : '' }}">Delivered</li>
            </ol>

            <div style="font-weight:700;margin:18px 0 10px">Status history</div>
            @forelse ($order->statusHistories as $history)
                <div style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px">
                    <div style="font-weight:600">{{ str_replace('_', ' ', $history->event) }} → {{ $history->to_status }}</div>
                    <div class="muted" style="font-size:12px">{{ $history->user?->name ?: 'System' }} · {{ $history->created_at?->format('d M H:i') }}</div>
                    @if ($history->notes)
                        <div class="muted" style="font-size:12px">{{ $history->notes }}</div>
                    @endif
                </div>
            @empty
                <div class="muted" style="font-size:13px">No history yet.</div>
            @endforelse
        </div>
    </div>
@endsection
