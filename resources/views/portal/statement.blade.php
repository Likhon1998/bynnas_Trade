@extends('portal.layouts.app')
@section('title', 'Account statement')
@section('content')
    <x-page-header title="Account statement" subtitle="{{ $shop->name }} · purchases, payments and outstanding balance" />

    @if ((float) $summary['outstanding'] > 0)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fff7ed;color:#c2410c">
            Reminder: your current outstanding balance is <strong>{{ \App\Support\DemoData::taka($summary['outstanding']) }}</strong>.
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total purchases</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['total_purchases']) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total payments</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['total_payments']) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Outstanding</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['outstanding']) }}</div></div>
        <div class="card" style="padding:14px">
            <div class="muted" style="font-size:12px">Last payment</div>
            <div style="font-weight:700">{{ \App\Support\DemoData::taka($summary['last_payment_amount']) }}</div>
            <div class="muted" style="font-size:11px">{{ $summary['last_payment_date']?->format('d M Y') ?: '—' }}</div>
        </div>
    </div>

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
