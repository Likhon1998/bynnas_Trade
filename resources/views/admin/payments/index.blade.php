@extends('layouts.app')
@section('title', 'Payments')
@section('content')
    <x-page-header title="Payments" subtitle="Record receipts and verify against invoices">
        <x-slot:description>Verified payments reduce invoice balance and shop outstanding. Pending payments do not.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    @can('payments.create')
        <div class="card" style="padding:16px;margin-bottom:14px">
            <div style="font-weight:700;margin-bottom:12px">Record payment</div>
            <form method="post" action="{{ route('payments.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label class="label">Shop</label>
                    <select class="select" name="shop_id" required>
                        <option value="">Select shop</option>
                        @foreach ($shops as $shop)
                            <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->name }} (outstanding {{ \App\Support\DemoData::taka($shop->outstanding_balance) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label">Invoice (optional)</label>
                    <select class="select" name="invoice_id">
                        <option value="">Unallocated / shop-level</option>
                        @foreach ($openInvoices as $inv)
                            <option value="{{ $inv->id }}" @selected(old('invoice_id', request('invoice_id')) == $inv->id)>
                                {{ $inv->number }} · {{ $inv->shop?->name }} · bal {{ \App\Support\DemoData::taka($inv->balance) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="label">Amount (BDT)</label><input class="input" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required></div>
                <div class="field">
                    <label class="label">Method</label>
                    <select class="select" name="method" required>
                        @foreach (['bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'mobile_banking' => 'Mobile banking'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('method', 'bank_transfer') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="label">Reference</label><input class="input" name="reference" value="{{ old('reference') }}" placeholder="Txn / cheque #"></div>
                <div class="field"><label class="label">Paid at</label><input class="input" type="datetime-local" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}"></div>
                <div class="field" style="grid-column:1/-1"><label class="label">Notes</label><textarea class="input" name="notes" rows="2">{{ old('notes') }}</textarea></div>
                @can('payments.verify')
                    <label style="display:flex;gap:8px;align-items:center;font-size:13px;grid-column:1/-1">
                        <input type="checkbox" name="verify_now" value="1" @checked(old('verify_now'))> Verify immediately (apply to invoice / credit)
                    </label>
                @endcan
                <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit">Save payment</button></div>
            </form>
        </div>
    @endcan

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Payment #, ref, shop">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $value => $label)
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
                                @if ($payment->reference)<div class="muted" style="font-size:12px">{{ $payment->reference }}</div>@endif
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
                                        <form method="post" action="{{ route('payments.verify', $payment) }}" style="display:inline">@csrf<button class="btn btn-ghost" type="submit" style="font-size:12px">Verify</button></form>
                                        <details style="display:inline">
                                            <summary class="btn btn-ghost" style="font-size:12px;color:#b91c1c;cursor:pointer;list-style:none">Reject</summary>
                                            <form method="post" action="{{ route('payments.reject', $payment) }}" style="margin-top:6px">
                                                @csrf
                                                <input class="input" name="rejection_reason" required placeholder="Reason" style="margin-bottom:6px">
                                                <button class="btn btn-ghost" type="submit" style="color:#b91c1c">Confirm</button>
                                            </form>
                                        </details>
                                    @endcan
                                @elseif ($payment->verifier)
                                    <span class="muted" style="font-size:12px">by {{ $payment->verifier->name }}</span>
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
