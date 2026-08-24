@extends('layouts.app')
@section('title', $invoice->number)
@section('content')
    <x-page-header title="{{ $invoice->number }}" subtitle="{{ $invoice->shop?->name }} · {{ $invoice->statusLabel() }}">
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">Back</a>
        @if ($invoice->order)
            <a class="btn btn-ghost" href="{{ route('orders.show', $invoice->order) }}">Order</a>
        @endif
        @can('payments.create')
            <a class="btn btn-primary" href="{{ route('payments.index', ['invoice_id' => $invoice->id]) }}">Record payment</a>
        @endcan
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop</div><div style="font-weight:700">{{ $invoice->shop?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($invoice->total) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Paid</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($invoice->paid_amount) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Balance</div><div style="font-weight:700;color:{{ $invoice->balance > 0 ? '#b91c1c' : '#15803d' }}">{{ \App\Support\DemoData::taka($invoice->balance) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Due</div><div style="font-weight:700">{{ $invoice->due_at?->format('d M Y') ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop outstanding</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($invoice->shop?->outstanding_balance) }}</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:14px">
        <div class="card">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Line items</div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>SKU</th><th>Product</th><th>Qty</th><th>Unit</th><th>Line</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
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
            @if ($invoice->notes)
                <div style="padding:14px;border-top:1px solid #eee"><span class="muted">Notes:</span> {{ $invoice->notes }}</div>
            @endif
        </div>

        <div class="card">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Payments</div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Payment</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($invoice->payments as $payment)
                            <tr>
                                <td>
                                    <div style="font-weight:600">{{ $payment->number }}</div>
                                    <div class="muted" style="font-size:12px">{{ $payment->methodLabel() }}</div>
                                </td>
                                <td>{{ \App\Support\DemoData::taka($payment->amount) }}</td>
                                <td><x-badge :status="$payment->statusLabel()" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted" style="padding:16px;text-align:center">No payments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 14px;font-size:12px" class="muted">Created by {{ $invoice->creator?->name ?: 'System' }} · {{ $invoice->issued_at?->format('d M Y H:i') }}</div>
        </div>
    </div>
@endsection
