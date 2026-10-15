@extends('layouts.app')
@section('title', 'Salaries')
@section('content')
    <x-page-header title="Salesman salaries" subtitle="Fixed monthly salary, bonuses and payment history">
        <div class="muted">Paid this month: <strong>{{ \App\Support\DemoData::taka($monthPaid) }}</strong></div>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    @can('salaries.manage')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            <div class="card" style="padding:16px">
                <div style="font-weight:700;margin-bottom:12px">Set monthly salary</div>
                <form method="post" action="{{ route('salaries.store') }}" class="form-grid">
                    @csrf
                    <div class="field">
                        <label class="label">Salesman</label>
                        <select class="select" name="user_id" required>
                            @foreach ($rows as $row)
                                <option value="{{ $row['salesman']->id }}">{{ $row['salesman']->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label class="label">Monthly salary (৳)</label><input class="input" type="number" step="0.01" min="0" name="monthly_salary" required></div>
                    <div class="field"><label class="label">Effective from</label><input class="input" type="date" name="effective_from" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
                    <div class="field form-span"><label class="label">Notes</label><input class="input" name="notes"></div>
                    <div><button class="btn btn-primary" type="submit">Save salary</button></div>
                </form>
            </div>
            <div class="card" style="padding:16px">
                <div style="font-weight:700;margin-bottom:12px">Record salary payment</div>
                <form method="post" action="{{ route('salaries.pay') }}" class="form-grid">
                    @csrf
                    <div class="field">
                        <label class="label">Salesman</label>
                        <select class="select" name="user_id" required>
                            @foreach ($rows as $row)
                                <option value="{{ $row['salesman']->id }}">{{ $row['salesman']->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label class="label">Year</label><input class="input" type="number" name="year" value="{{ now()->year }}" required></div>
                    <div class="field"><label class="label">Month</label><input class="input" type="number" min="1" max="12" name="month" value="{{ now()->month }}" required></div>
                    <div class="field"><label class="label">Amount (৳)</label><input class="input" type="number" step="0.01" min="0" name="amount" required></div>
                    <div class="field"><label class="label">Bonus (৳)</label><input class="input" type="number" step="0.01" min="0" name="bonus" value="0"></div>
                    <div class="field"><label class="label">Method</label><input class="input" name="method" placeholder="Bank / Cash / bKash"></div>
                    <div><button class="btn btn-primary" type="submit">Mark paid</button></div>
                </form>
            </div>
        </div>
    @endcan

    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Current salaries</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Salesman</th><th>Monthly salary</th><th>Effective</th><th>Unpaid months</th><th>Last payment</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td><a class="link" href="{{ route('salesmen.show', $row['salesman']) }}">{{ $row['salesman']->name }}</a></td>
                            <td>{{ $row['current'] ? \App\Support\DemoData::taka($row['current']->monthly_salary) : '—' }}</td>
                            <td>{{ $row['current']?->effective_from?->format('d M Y') ?: '—' }}</td>
                            <td>{{ $row['outstanding_months'] }}</td>
                            <td>
                                @if ($row['last_payment'])
                                    {{ $row['last_payment']->periodLabel() }} · {{ \App\Support\DemoData::taka($row['last_payment']->total()) }}
                                    <span class="muted">({{ $row['last_payment']->status }})</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Payment history</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>Salesman</th><th>Amount</th><th>Bonus</th><th>Method</th><th>Status</th><th>Paid at</th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->periodLabel() }}</td>
                            <td>{{ $payment->user?->name }}</td>
                            <td>{{ \App\Support\DemoData::taka($payment->amount) }}</td>
                            <td>{{ \App\Support\DemoData::taka($payment->bonus) }}</td>
                            <td>{{ $payment->method ?: '—' }}</td>
                            <td>{{ ucfirst($payment->status) }}</td>
                            <td>{{ $payment->paid_at?->format('d M Y') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:16px">No salary payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px">{{ $payments->links() }}</div>
    </div>
@endsection
