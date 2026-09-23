@extends('layouts.app')
@section('title', $order->number)
@section('content')
@php
    $statusTone = $payload['status_tone'] ?? 'muted';
    $timeline = $purchase['timeline'] ?? [];
    $invoices = $purchase['invoices'] ?? [];
    $payments = $purchase['payments'] ?? [];
@endphp
<div class="order-page" x-data="{ tab: 'timeline', requireAdvance: false, auditTab: 'approve', previewId: null }" @keydown.escape.window="previewId = null">
    <x-page-header title="{{ $order->number }}" subtitle="{{ $order->shop?->name }} · {{ $order->shop?->code }} · {{ ucfirst(str_replace('_', ' ', $order->source)) }}">
        <span class="status-pill status-pill--{{ $statusTone }}">{{ $order->statusLabel() }}</span>
        <a class="btn btn-ghost" href="{{ route('orders.index') }}">Back to orders</a>
    </x-page-header>

    @if (session('success'))
        <div class="status-flash status-flash--{{ session('status_flash.result', 'updated') }}" role="status">
            <div class="status-flash-main">{{ session('success') }}</div>
            @if (session('invoice_download'))
                <div class="status-flash-meta" style="margin-top:8px">
                    <a class="btn btn-primary btn-sm" href="{{ session('invoice_download') }}">Download invoice PDF</a>
                </div>
            @endif
        </div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
            <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="order-page-summary">
        <div><span>Shop</span><strong>{{ $order->shop?->name ?: '—' }}</strong></div>
        <div><span>Salesman</span><strong>{{ $order->salesman?->name ?: '—' }}</strong></div>
        <div><span>Submitted</span><strong>{{ $order->submitted_at?->format('l, d M Y · h:i A') ?: '—' }}</strong></div>
        <div><span>Lines</span><strong>{{ $order->item_count }}</strong></div>
        <div><span>Total</span><strong>{{ \App\Support\DemoData::taka($order->total) }}</strong></div>
            <div>
            <span>Stock</span>
            <strong style="color:{{ $snapshot['stock_ok'] ? '#15803d' : '#b91c1c' }}">
                {{ $snapshot['stock_ok'] ? 'Ready to reserve' : 'Shortfall' }}
            </strong>
        </div>
        </div>

    @php $settlement = $order->settlement(); @endphp
    @if ($settlement['has_advance'] || $settlement['remaining_due'] > 0.009 || $settlement['advance_paid'] > 0.009)
        <div class="order-settlement {{ $settlement['fully_settled'] ? 'is-settled' : ($settlement['advance_cleared'] ? 'is-partial' : 'is-due') }}">
            <div class="order-settlement-head">
                <strong>Payment settlement</strong>
                <span>
                    @if ($settlement['fully_settled'])
                        Fully settled
                    @elseif ($settlement['advance_cleared'] && $settlement['remaining_due'] > 0.009)
                        Advance received — remaining still due
                    @elseif ($settlement['has_advance'] && ! $settlement['advance_cleared'])
                        Advance still due
                    @else
                        Balance outstanding
    @endif
                </span>
            </div>
            <div class="order-settlement-grid">
                <div>
                    <span>Order total</span>
                    <strong>{{ \App\Support\DemoData::taka($settlement['order_total']) }}</strong>
                </div>
                <div>
                    <span>Advance paid</span>
                    <strong>{{ \App\Support\DemoData::taka($settlement['advance_paid']) }}</strong>
            </div>
                @if ($settlement['sales_paid'] > 0.009)
                    <div>
                        <span>Final invoice paid</span>
                        <strong>{{ \App\Support\DemoData::taka($settlement['sales_paid']) }}</strong>
                    </div>
                @endif
                <div class="order-settlement-rest">
                    <span>Remaining due</span>
                    <strong>{{ \App\Support\DemoData::taka($settlement['remaining_due']) }}</strong>
                    <em>
                        @if ($settlement['fully_settled'])
                            Nothing left to collect
                        @elseif ($order->invoice && (int) $order->invoice_id !== (int) $order->advance_invoice_id)
                            On sales invoice {{ $order->invoice->number }}
                        @else
                            Acknowledged after advance — collect on final invoice / delivery
                        @endif
                    </em>
                    </div>
            </div>
        </div>
    @endif

    <div class="order-page-layout">
        <div class="order-page-main">
            {{-- Purchase dossier --}}
            <section class="card order-page-card">
                <div class="order-page-card-head">
                    <div>
                        <strong>Purchase history</strong>
                        <div class="muted" style="font-size:12px;margin-top:2px">Every stage, invoice, and payment with day · date · time</div>
                    </div>
                    <div class="order-history-tabs">
                        <button type="button" class="order-history-tab" :class="tab === 'timeline' && 'is-active'" @click="tab = 'timeline'">Timeline ({{ count($timeline) }})</button>
                        <button type="button" class="order-history-tab" :class="tab === 'invoices' && 'is-active'" @click="tab = 'invoices'">Invoices ({{ $invoicePreviews->count() }})</button>
                        <button type="button" class="order-history-tab" :class="tab === 'payments' && 'is-active'" @click="tab = 'payments'">Payments ({{ count($payments) }})</button>
                    </div>
                </div>

                <div class="order-page-card-body" x-show="tab === 'timeline'">
                    @forelse ($timeline as $ev)
                        <div class="order-timeline-item tone-{{ $ev['tone'] ?? 'info' }}">
                            <div class="order-timeline-stamp">
                                <span class="order-timeline-day">{{ $ev['day'] ?? '' }}</span>
                                <span class="order-timeline-date">{{ $ev['date'] ?? '' }}</span>
                                <span class="order-timeline-time">{{ $ev['time'] ?? '' }}</span>
                            </div>
                            <div class="order-timeline-body">
                                <strong>{{ $ev['title'] }}</strong>
                                @if (!empty($ev['detail']))
                                    <p>{{ $ev['detail'] }}</p>
                                @endif
                                @if (!empty($ev['by']))
                                    <em>By {{ $ev['by'] }}</em>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="order-history-empty">No stage history yet.</div>
                    @endforelse
                </div>

                <div class="order-page-card-body" x-show="tab === 'invoices'" x-cloak>
                    @forelse ($invoicePreviews as $preview)
                        @php $inv = $preview['invoice']; @endphp
                        <article class="order-invoice-card" style="margin-bottom:10px">
                            <div class="order-invoice-top">
                                <div>
                                    <span class="order-invoice-kicker">{{ $preview['title'] }}</span>
                                    <strong>{{ $inv->number }}</strong>
                                </div>
                                <span class="badge
                                    @if ($inv->status === 'paid') badge-paid
                                    @elseif ($inv->status === 'partial') badge-partial
                                    @elseif ($inv->status === 'issued') badge-pending
                                    @else badge-hold
                                    @endif">{{ $inv->statusLabel() }}</span>
                            </div>
                            <div class="order-invoice-amounts">
                                <div><span>Total</span><strong>{{ \App\Support\DemoData::taka($inv->total) }}</strong></div>
                                <div><span>Paid</span><strong>{{ \App\Support\DemoData::taka($inv->paid_amount) }}</strong></div>
                                <div><span>Balance</span><strong>{{ \App\Support\DemoData::taka($inv->balance) }}</strong></div>
                            </div>
                            @if ($inv->issued_at)
                                <div class="order-history-meta" style="margin-top:8px">
                                    Issued <strong>{{ $inv->issued_at->format('l, d M Y · h:i A') }}</strong>
            </div>
    @endif
                            <div class="order-invoice-actions">
                                <button type="button" class="btn btn-primary btn-sm" @click="previewId = {{ $inv->id }}">Preview</button>
                                <a class="btn btn-ghost btn-sm" href="{{ route('invoices.download', $inv) }}">Download PDF</a>
                                <a class="btn btn-ghost btn-sm" href="{{ route('invoices.show', $inv) }}">Open</a>
                            </div>
                        </article>
                    @empty
                        <div class="order-history-empty">No invoices raised for this purchase yet.</div>
                    @endforelse
                </div>

                <div class="order-page-card-body" x-show="tab === 'payments'" x-cloak>
                    @forelse ($payments as $pay)
                        <article class="order-history-item is-{{ $pay['status_key'] }}" style="margin-bottom:8px">
                            <div class="order-history-top">
                                <strong>{{ $pay['amount'] }}</strong>
                                <span class="badge
                                    @if ($pay['status_key'] === 'verified') badge-paid
                                    @elseif ($pay['status_key'] === 'pending') badge-pending
                                    @else badge-out
                                    @endif">{{ $pay['status'] }}</span>
                            </div>
                            <div class="order-history-meta">
                                {{ $pay['kind'] }} · {{ $pay['method'] }}
                                @if (!empty($pay['invoice'])) · {{ $pay['invoice'] }} @endif
                                @if (!empty($pay['number'])) · {{ $pay['number'] }} @endif
                            </div>
                            <div class="order-invoice-stamp">
                                <span class="order-invoice-kicker">Paid</span>
                                <div class="order-timeline-stamp">
                                    <span class="order-timeline-day">{{ $pay['paid_day'] ?? '' }}</span>
                                    <span class="order-timeline-date">{{ $pay['paid_date'] ?? $pay['paid_at'] }}</span>
                                    <span class="order-timeline-time">{{ $pay['paid_time'] ?? '' }}</span>
                                </div>
                            </div>
                            @if (!empty($pay['verified_at']))
                                <div class="order-invoice-stamp">
                                    <span class="order-invoice-kicker">Verified</span>
                                    <div class="order-timeline-stamp">
                                        <span class="order-timeline-day">{{ $pay['verified_day'] ?? '' }}</span>
                                        <span class="order-timeline-date">{{ $pay['verified_date'] ?? $pay['verified_at'] }}</span>
                                        <span class="order-timeline-time">{{ $pay['verified_time'] ?? '' }}</span>
                                    </div>
                                    @if (!empty($pay['verified_by']))
                                        <div class="order-history-meta" style="margin-top:2px">by {{ $pay['verified_by'] }}</div>
    @endif
                                </div>
    @endif
                            @if (!empty($pay['reference']) || !empty($pay['recorded_by']))
                                <div class="order-history-meta">
                                    @if (!empty($pay['reference'])) Ref: {{ $pay['reference'] }} @endif
                                    @if (!empty($pay['recorded_by'])) · Recorded by {{ $pay['recorded_by'] }} @endif
        </div>
    @endif
                        </article>
                    @empty
                        <div class="order-history-empty">No payments recorded for this purchase yet.</div>
                    @endforelse
                </div>
            </section>

            {{-- Items --}}
            <section class="card order-page-card">
                <div class="order-page-card-head">
                    <strong>Order items</strong>
                    <span class="muted" style="font-size:12px">{{ $order->item_count }} line(s) · {{ \App\Support\DemoData::taka($order->total) }}</span>
                </div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                                <th>Item</th>
                                <th class="num">Qty</th>
                                <th class="num">Stock</th>
                                <th class="num">Reserved</th>
                                <th class="num">Unit</th>
                                <th class="num">Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            @php
                                $avail = $item->product?->availableStock() ?? ($item->available_at_audit ?? 0);
                                $ok = $avail >= $item->quantity || $item->reserved_quantity > 0;
                            @endphp
                            <tr>
                                    <td>
                                        <div style="font-weight:700">{{ $item->product_name }}</div>
                                        <div class="muted" style="font-size:11px">{{ $item->product_sku }}</div>
                                    </td>
                                    <td class="num" style="font-weight:800">{{ $item->quantity }}</td>
                                    <td class="num" style="color:{{ $ok ? '#15803d' : '#b91c1c' }};font-weight:700">{{ number_format($avail) }}</td>
                                    <td class="num">{{ $item->reserved_quantity }}</td>
                                    <td class="num">{{ \App\Support\DemoData::taka($item->unit_price) }}</td>
                                    <td class="num" style="font-weight:700">{{ \App\Support\DemoData::taka($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($order->notes)
                    <div style="padding:14px;border-top:1px solid var(--line)"><span class="muted">Shop notes:</span> {{ $order->notes }}</div>
            @endif
            </section>
        </div>

        <aside class="order-page-aside">
            @if ($order->fulfilment)
                <div class="card" style="padding:14px;margin-bottom:12px">
                    <div style="font-weight:800;margin-bottom:4px">Warehouse fulfilment</div>
                    <div class="muted" style="font-size:12px;margin-bottom:10px">{{ $order->fulfilment->statusLabel() }} · {{ $order->warehouse?->name ?: $order->fulfilment->warehouse?->name }}</div>
                    <a class="btn btn-primary btn-sm" href="{{ route('fulfilment.show', $order->fulfilment) }}">Open fulfilment</a>
                </div>
            @endif

            @if ($order->rejection_reason)
                <div class="card" style="padding:12px 14px;margin-bottom:12px;background:#fef2f2;color:#b91c1c">Rejected: {{ $order->rejection_reason }}</div>
            @endif
            @if ($order->cancellation_reason)
                <div class="card" style="padding:12px 14px;margin-bottom:12px;background:#f3f4f6">Cancelled: {{ $order->cancellation_reason }}</div>
            @endif

            {{-- Advance --}}
            @if ($payload['awaiting_advance'] ?? false)
                <div class="card order-advance-box" style="margin-bottom:12px">
                    @if ($payload['advance_paid'] ?? false)
                        <strong style="color:#15803d">Advance paid</strong>
                        <div class="order-advance-amount">{{ $payload['advance_amount'] }}</div>
                        <p class="muted" style="margin:6px 0 0;font-size:12px">
                            You can approve &amp; reserve now.
                        </p>
                        <div class="order-settlement-mini">
                            <div><span>Order total</span><strong>{{ $payload['settlement']['order_total'] ?? \App\Support\DemoData::taka($order->total) }}</strong></div>
                            <div><span>Advance paid</span><strong>{{ $payload['settlement']['advance_paid'] ?? $payload['advance_amount'] }}</strong></div>
                            <div class="is-rest"><span>Remaining due</span><strong>{{ $payload['settlement']['remaining_due'] ?? '—' }}</strong></div>
                        </div>
                        <p class="muted" style="margin:8px 0 0;font-size:12px">
                            Remaining is acknowledged and will be collected on the final sales invoice after fulfilment.
                        </p>
                    @else
                        <strong>Advance due</strong>
                        <div class="order-advance-amount">{{ $payload['advance_amount'] }}</div>
                        <p class="muted" style="margin:4px 0 8px;font-size:12px">
                            Balance {{ $payload['advance_balance'] }}
                            @if (!empty($payload['advance_invoice_number'])) · Invoice {{ $payload['advance_invoice_number'] }} @endif
                        </p>
                        <div class="order-settlement-mini">
                            <div><span>Order total</span><strong>{{ $payload['settlement']['order_total'] ?? \App\Support\DemoData::taka($order->total) }}</strong></div>
                            <div class="is-rest"><span>After advance, remaining</span><strong>{{ $payload['settlement']['remaining_due'] ?? '—' }}</strong></div>
                        </div>
                        @if (!empty($payload['collect_advance_url']))
                            <form method="post" action="{{ $payload['collect_advance_url'] }}" class="order-decide-panel" style="gap:8px">
                                @csrf
                                <label class="order-field">
                                    <span class="order-field-label">Amount received</span>
                                    <input class="input" type="number" name="amount" step="0.01" min="0.01" value="{{ $payload['advance_balance_raw'] }}" required>
                                </label>
                                <label class="order-field">
                                    <span class="order-field-label">Method</span>
                                    <select class="select" name="method" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                        <option value="mobile_banking">Mobile banking</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </label>
                                <label class="order-field">
                                    <span class="order-field-label">Reference <em>(optional)</em></span>
                                    <input class="input" type="text" name="reference" placeholder="Txn / cheque no.">
                                </label>
                                <button class="btn btn-primary btn-block" type="submit">Mark advance received</button>
                            </form>
                        @endif
                        @if (!empty($payload['advance_invoice_id'] ?? null) || $order->advance_invoice_id)
                            <a class="btn btn-ghost btn-sm" style="margin-top:8px" href="{{ route('invoices.download', $order->advance_invoice_id) }}">Download advance invoice</a>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Audit actions --}}
            @if ($payload['pending_audit'] ?? false)
                @if (($payload['can_approve'] ?? false) || ($payload['can_request_advance'] ?? false) || ($payload['can_reject'] ?? false))
                    <div class="card" style="padding:14px;margin-bottom:12px">
                        <div style="font-weight:800;margin-bottom:10px">Decide</div>

                        @if (!($payload['awaiting_advance'] ?? false))
                            <div class="order-decide-tabs" style="margin-bottom:10px">
                                <button type="button" class="order-decide-tab" :class="auditTab === 'approve' && 'is-active'" @click="auditTab = 'approve'">Approve</button>
                                @if ($payload['can_reject'] ?? false)
                                    <button type="button" class="order-decide-tab is-danger" :class="auditTab === 'reject' && 'is-active'" @click="auditTab = 'reject'">Reject</button>
                                @endif
                            </div>

                            <div x-show="auditTab === 'approve'">
                                <label class="order-override" style="border-color:#c7d2fe;background:#eef2ff;cursor:pointer;margin-bottom:10px">
                                    <input type="checkbox" x-model="requireAdvance">
                                    <span>
                                        <strong style="color:#3730a3">Require advance first</strong>
                                        <small style="color:#4338ca">Shop must pay before approval</small>
                                    </span>
                                </label>

                                <form method="post" action="{{ $payload['request_advance_url'] }}" x-show="requireAdvance" x-cloak>
                                    @csrf
                                    <label class="order-field">
                                        <span class="order-field-label">Note <em>(optional)</em></span>
                                        <input class="input" type="text" name="audit_notes" placeholder="Warehouse / credit note">
                                    </label>
                                    <label class="order-field">
                                        <span class="order-field-label">Advance amount (৳)</span>
                                        <input class="input" type="number" name="advance_amount" step="0.01" min="1" max="{{ $payload['total_raw'] }}" value="{{ old('advance_amount', round($payload['total_raw'] * 0.3, 2)) }}" required>
                                    </label>
                                    <button class="btn btn-primary btn-block" type="submit">Request advance</button>
                                </form>

                                @if ($payload['can_approve'] ?? false)
                                    <form method="post" action="{{ $payload['approve_url'] }}" x-show="!requireAdvance">
                                        @csrf
                                        <label class="order-field">
                                            <span class="order-field-label">Note <em>(optional)</em></span>
                                            <input class="input" type="text" name="audit_notes" placeholder="Warehouse / credit note">
                                        </label>
                                        @unless ($snapshot['stock_ok'])
                                            <p class="order-decide-hint">Stock shortfall — cannot approve.</p>
                                        @endunless
                                        <button class="btn btn-approve btn-block" type="submit" @disabled(! $snapshot['stock_ok'])>Approve &amp; reserve</button>
                                    </form>
                                @endif
                            </div>

                            @if ($payload['can_reject'] ?? false)
                                <form method="post" action="{{ $payload['reject_url'] }}" x-show="auditTab === 'reject'" x-cloak>
                                    @csrf
                                    <label class="order-field">
                                        <span class="order-field-label">Rejection reason</span>
                                        <input class="input" type="text" name="rejection_reason" required placeholder="Why reject?">
                                    </label>
                                    <button class="btn btn-reject btn-block" type="submit">Reject order</button>
                                </form>
                            @endif
                        @else
                            @if (($payload['can_approve'] ?? false) && ($payload['advance_paid'] ?? false))
                                <form method="post" action="{{ $payload['approve_url'] }}">
                                    @csrf
                                    <label class="order-field">
                                        <span class="order-field-label">Note <em>(optional)</em></span>
                                        <input class="input" type="text" name="audit_notes" placeholder="Warehouse note">
                                    </label>
                                    @unless ($snapshot['stock_ok'])
                                        <p class="order-decide-hint">Stock shortfall — cannot approve.</p>
                                    @endunless
                                    <button class="btn btn-approve btn-block" type="submit" @disabled(! $snapshot['stock_ok'])>Approve &amp; reserve</button>
                                </form>
                            @endif
                            @if ($payload['can_reject'] ?? false)
                                <form method="post" action="{{ $payload['reject_url'] }}" style="margin-top:10px">
                                    @csrf
                                    <label class="order-field">
                                        <span class="order-field-label">Rejection reason</span>
                                        <input class="input" type="text" name="rejection_reason" required placeholder="Why reject?">
                                    </label>
                                    <button class="btn btn-reject btn-block" type="submit">Reject order</button>
                                </form>
                            @endif
                        @endif

                        @if ($payload['can_delete'] ?? false)
                            <form method="post" action="{{ $payload['delete_url'] }}" style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--line)" onsubmit="return confirm('Delete this order permanently?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn-link-danger" type="submit">Delete this order</button>
                            </form>
                        @endif
                    </div>
                @endif
            @endif

            @if ($order->isApproved() || $order->isPendingAudit() || $order->isAwaitingAdvance())
                @can('cancel', $order)
                    <div class="card" style="padding:14px;margin-bottom:12px">
                        <form method="post" action="{{ route('orders.cancel', $order) }}">
                            @csrf
                            <label class="order-field">
                                <span class="order-field-label">Cancel reason</span>
                                <input class="input" name="cancellation_reason" required placeholder="Reason for cancellation">
                            </label>
                            <button class="btn btn-ghost btn-block" type="submit">Cancel order{{ $order->isApproved() ? ' & release stock' : '' }}</button>
                        </form>
                    </div>
                @endcan
            @endif

            @if ($order->advance_invoice_id || $order->invoice_id)
                <div class="card" style="padding:14px;margin-bottom:12px">
                    <div style="font-weight:800;margin-bottom:8px">Invoices</div>
                    <div style="display:grid;gap:6px">
                        @foreach ($invoicePreviews as $preview)
                            <button
                                type="button"
                                class="btn btn-ghost btn-sm"
                                style="justify-content:flex-start"
                                @click="tab = 'invoices'; previewId = {{ $preview['invoice']->id }}"
                            >
                                Preview {{ strtolower($preview['title']) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($order->invoice)
                <div class="card" style="padding:14px;margin-bottom:12px">
                    <div style="font-weight:800">Sales invoice</div>
                    <div class="muted" style="font-size:12px;margin:4px 0 10px">{{ $order->invoice->number }} · {{ $order->invoice->statusLabel() }}</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button type="button" class="btn btn-primary btn-sm" @click="tab = 'invoices'; previewId = {{ $order->invoice->id }}">Preview</button>
                        <a class="btn btn-ghost btn-sm" href="{{ route('invoices.download', $order->invoice) }}">Download PDF</a>
                        <a class="btn btn-ghost btn-sm" href="{{ route('invoices.show', $order->invoice) }}">Open</a>
                    </div>
                </div>
            @elseif (in_array($order->status, ['delivered', 'dispatched', 'packed', 'picked', 'approved'], true))
                @can('invoices.create')
                    <div class="card" style="padding:14px;margin-bottom:12px">
                        <div style="font-weight:800;margin-bottom:6px">No sales invoice yet</div>
                        <form method="post" action="{{ route('orders.invoice', $order) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Raise invoice</button></form>
                    </div>
                @endcan
            @endif

            <div class="card" style="padding:14px">
                <div style="font-weight:800;margin-bottom:10px">Pipeline</div>
                <ol class="muted" style="padding-left:18px;line-height:1.85;margin:0;font-size:13px">
                    <li>Submitted for audit</li>
                    <li style="{{ $order->advance_required ? 'color:#d97706;font-weight:600' : '' }}">Advance (if required)</li>
                    <li style="{{ $order->stock_reserved || in_array($order->status, ['approved','picking','picked','packed','dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Approved · stock reserved</li>
                    <li style="{{ in_array($order->status, ['picked','packed','dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Picked</li>
                    <li style="{{ in_array($order->status, ['packed','dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Packed</li>
                    <li style="{{ in_array($order->status, ['dispatched','delivered'], true) ? 'color:#15803d;font-weight:600' : '' }}">Dispatched</li>
                    <li style="{{ $order->status === 'delivered' ? 'color:#15803d;font-weight:600' : '' }}">Delivered</li>
                </ol>
            </div>
        </aside>
    </div>

    {{-- Invoice preview modal (opens only on Preview click) --}}
    @foreach ($invoicePreviews as $preview)
        <div
            class="invoice-preview-modal"
            x-show="previewId === {{ $preview['invoice']->id }}"
            x-cloak
            @click.self="previewId = null"
        >
            <div class="invoice-preview-modal-panel" @click.stop>
                <div class="invoice-preview-toolbar">
                    <div>
                        <strong>{{ $preview['title'] }} preview</strong>
                        <div class="muted" style="font-size:12px">{{ $preview['invoice']->number }} · same layout as the PDF</div>
                    </div>
                    <div class="order-invoice-actions">
                        <a class="btn btn-primary btn-sm" href="{{ route('invoices.download', $preview['invoice']) }}">Download PDF</a>
                        <button type="button" class="btn btn-ghost btn-sm" @click="previewId = null">Close</button>
                    </div>
                </div>
                <div class="invoice-preview-sheet">
                    @include('admin.invoices.partials.document-body', [
                        'invoice' => $preview['invoice'],
                        'documentKind' => $preview['kind'],
                    ])
                </div>
            </div>
        </div>
    @endforeach
    </div>
@endsection
