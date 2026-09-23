@extends('layouts.app')
@section('title', $invoice->number)
@section('content')
@php
    $documentKind = str_contains(strtolower((string) $invoice->notes), 'advance')
        ? 'Advance Invoice'
        : (($invoice->order && (int) $invoice->order->advance_invoice_id === (int) $invoice->id)
            ? 'Advance Invoice'
            : 'Sales Invoice');
@endphp
<div x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
    <x-page-header title="{{ $invoice->number }}" subtitle="{{ $invoice->shop?->name }} · {{ $invoice->statusLabel() }}">
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">Back</a>
        @if ($invoice->order)
            <a class="btn btn-ghost" href="{{ route('orders.show', $invoice->order) }}">Order</a>
        @endif
        <button type="button" class="btn btn-primary" @click="previewOpen = true">Preview</button>
        <a class="btn btn-ghost" href="{{ route('invoices.download', $invoice) }}">Download PDF</a>
        @can('payments.create')
            <a class="btn btn-ghost" href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}">Record payment</a>
        @endcan
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
            <span>{{ session('success') }}</span>
            @if (session('invoice_download'))
                <a class="btn btn-primary btn-sm" href="{{ session('invoice_download') }}">Download invoice PDF</a>
            @endif
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Shop</div><div style="font-weight:700">{{ $invoice->shop?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($invoice->total) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Paid</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($invoice->paid_amount) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Balance</div><div style="font-weight:700;color:{{ $invoice->balance > 0 ? '#b91c1c' : '#15803d' }}">{{ \App\Support\DemoData::taka($invoice->balance) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Due</div><div style="font-weight:700">{{ $invoice->due_at?->format('d M Y') ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Issued</div><div style="font-weight:700">{{ $invoice->issued_at?->format('d M Y H:i') ?: '—' }}</div></div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:14px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
        <div>
            <div style="font-weight:800">{{ $documentKind }}</div>
            <div class="muted" style="font-size:12px;margin-top:2px">Click Preview to see the full invoice with payment history</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="btn btn-primary" @click="previewOpen = true">Preview invoice</button>
            <a class="btn btn-ghost" href="{{ route('invoices.download', $invoice) }}">Download PDF</a>
        </div>
    </div>

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
    </div>

    <div
        class="invoice-preview-modal"
        x-show="previewOpen"
        x-cloak
        @click.self="previewOpen = false"
    >
        <div class="invoice-preview-modal-panel" @click.stop>
            <div class="invoice-preview-toolbar">
                <div>
                    <strong>Invoice preview</strong>
                    <div class="muted" style="font-size:12px">{{ $invoice->number }} · includes payment history</div>
                </div>
                <div class="order-invoice-actions">
                    <a class="btn btn-primary btn-sm" href="{{ route('invoices.download', $invoice) }}">Download PDF</a>
                    <button type="button" class="btn btn-ghost btn-sm" @click="previewOpen = false">Close</button>
                </div>
            </div>
            <div class="invoice-preview-sheet">
                @include('admin.invoices.partials.document-body', [
                    'invoice' => $invoice,
                    'documentKind' => $documentKind,
                ])
            </div>
        </div>
    </div>
</div>
@endsection
