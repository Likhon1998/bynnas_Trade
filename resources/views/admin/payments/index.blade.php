@extends('layouts.app')
@section('title', 'Payments')
@section('content')
    <x-page-header title="Payments" subtitle="Receipts · verify against invoices">
        <x-slot:description>Verified payments reduce invoice balance and shop outstanding.</x-slot:description>
        @can('payments.create')
            <a class="btn btn-primary" href="{{ route('payments.create') }}">Record payment</a>
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
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="padding:12px;margin-bottom:14px">
        <form class="filters" method="get" x-data="{ search: @js(request('search', '')), status: @js(request('status', '')) }" @submit.prevent="
            const u = new URL(window.location.href);
            if (search) u.searchParams.set('search', search); else u.searchParams.delete('search');
            if (status) u.searchParams.set('status', status); else u.searchParams.delete('status');
            window.location = u.pathname + u.search;
        ">
            <input class="input input-sm" type="search" name="search" x-model="search" @input.debounce.300ms="$el.form.requestSubmit()" placeholder="Payment #, ref, shop" style="min-width:160px;flex:1" autocomplete="off">
            <select class="select select-sm" name="status" x-model="status" @change="$el.form.requestSubmit()">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data data-dense">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Shop</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $payment->number }}</div>
                                @if ($payment->reference)<div class="muted" style="font-size:11px">{{ $payment->reference }}</div>@endif
                            </td>
                            <td>{{ $payment->shop?->name }}</td>
                            <td class="muted">
                                @if ($payment->invoice)
                                    <a class="link" href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($payment->amount) }}</td>
                            <td class="muted">{{ $payment->paid_at?->format('d M Y') }}</td>
                            <td><x-badge :status="$payment->statusLabel()" /></td>
                            <td>
                                @if ($payment->status === 'pending')
                                    @can('payments.verify')
                                        <form method="post" action="{{ route('payments.verify', $payment) }}" style="display:inline">@csrf<button class="btn btn-ghost btn-sm" type="submit">Verify</button></form>
                                        <details style="display:inline">
                                            <summary class="btn btn-ghost btn-sm" style="color:#b91c1c;cursor:pointer;list-style:none">Reject</summary>
                                            <form method="post" action="{{ route('payments.reject', $payment) }}" style="margin-top:6px">
                                                @csrf
                                                <input class="input" name="rejection_reason" required placeholder="Reason" style="margin-bottom:6px">
                                                <button class="btn btn-ghost btn-sm" type="submit" style="color:#b91c1c">Confirm</button>
                                            </form>
                                        </details>
                                    @endcan
                                @elseif ($payment->verifier)
                                    <span class="muted" style="font-size:11px">by {{ $payment->verifier->name }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div style="padding:12px 16px">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection
