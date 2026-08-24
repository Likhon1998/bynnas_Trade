@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
    <x-page-header title="Invoices" subtitle="Issued from delivered orders · credit outstanding">
        <x-slot:description>Invoices raise shop AR. Open balances feed credit hold when over limit.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Invoice #, shop, order">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['issued' => 'Issued', 'partial' => 'Partially paid', 'paid' => 'Paid', 'void' => 'Void'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Shop</th>
                        <th>Order</th>
                        <th>Issued</th>
                        <th>Due</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><a class="link" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                            <td>{{ $invoice->shop?->name }}</td>
                            <td class="muted">{{ $invoice->order?->number ?: '—' }}</td>
                            <td class="muted">{{ $invoice->issued_at?->format('d M Y') }}</td>
                            <td class="muted">{{ $invoice->due_at?->format('d M Y') }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($invoice->total) }}</td>
                            <td style="font-weight:600;color:{{ $invoice->balance > 0 ? '#b91c1c' : '#15803d' }}">{{ \App\Support\DemoData::taka($invoice->balance) }}</td>
                            <td><x-badge :status="$invoice->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">No invoices yet. Deliver an order to auto-raise one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())
            <div style="padding:12px 16px">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
