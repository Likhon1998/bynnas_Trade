@extends('portal.layouts.app')
@section('title', 'Account statement')
@section('content')
    <x-page-header title="Account statement" subtitle="{{ $shop->name }} · purchases, payments and baki" />

    @if ((float) $summary['outstanding'] > 0)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fff7ed;color:#c2410c">
            Reminder: your current baki is <strong>{{ \App\Support\DemoData::taka($summary['outstanding']) }}</strong>. This is unpaid from delivered orders.
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total purchases</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['total_purchases']) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total payments</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['total_payments']) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Baki</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['outstanding']) }}</div></div>
        <div class="card" style="padding:14px">
            <div class="muted" style="font-size:12px">Last payment</div>
            <div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['last_payment_amount']) }}</div>
            <div class="muted" style="font-size:11px">{{ $summary['last_payment_date']?->format('d M Y') ?: '—' }}</div>
        </div>
    </div>

    @if (($receipts ?? collect())->isNotEmpty())
        <div class="card" style="margin-bottom:14px">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Money you paid</div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Collected by</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($receipts as $payment)
                            <tr>
                                <td>{{ $payment->paid_at?->format('d M Y') ?: $payment->created_at?->format('d M Y') }}</td>
                                <td style="font-weight:800">{{ \App\Support\DemoData::taka($payment->amount) }}</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td>{{ $payment->creator?->name ?: 'Office' }}</td>
                                <td>{{ $payment->statusLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Description</th><th>Status</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ optional($entry['at'])->format('d M Y') }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td>{{ $entry['status'] }}</td>
                            <td>{{ $entry['debit'] > 0 ? \App\Support\DemoData::taka($entry['debit']) : '—' }}</td>
                            <td>{{ $entry['credit'] > 0 ? \App\Support\DemoData::taka($entry['credit']) : '—' }}</td>
                            <td>{{ \App\Support\DemoData::taka($entry['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:16px">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
