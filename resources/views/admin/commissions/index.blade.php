@extends('layouts.app')
@section('title', 'Commissions')
@section('content')
    <x-page-header title="Commissions" subtitle="Verify payment → Accrue → Approve → Pay">
        <x-slot:description>Collection commission accrues automatically when a payment is verified on an order/shop with a salesman. Target bonus accrues when the monthly sales target is met.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="commission-pipeline">
        <div class="commission-pipe is-accrued">
            <span class="commission-pipe__label">1 · Accrued</span>
            <strong>{{ \App\Support\DemoData::taka($pipeline['accrued'] ?? 0) }}</strong>
            <em>{{ (int) ($pipeline['count_accrued'] ?? 0) }} awaiting approval</em>
        </div>
        <div class="commission-pipe is-approved">
            <span class="commission-pipe__label">2 · Approved</span>
            <strong>{{ \App\Support\DemoData::taka($pipeline['approved'] ?? 0) }}</strong>
            <em>{{ (int) ($pipeline['count_approved'] ?? 0) }} ready to pay</em>
        </div>
        <div class="commission-pipe is-paid">
            <span class="commission-pipe__label">3 · Paid</span>
            <strong>{{ \App\Support\DemoData::taka($pipeline['paid'] ?? 0) }}</strong>
            <em>{{ (int) ($pipeline['count_paid'] ?? 0) }} settled</em>
        </div>
    </div>

    @can('commissions.manage')
        <div class="card" style="padding:16px;margin-bottom:14px">
            <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:flex-end">
                <div style="flex:1;min-width:240px">
                    <div style="font-weight:700;margin-bottom:12px">Commission rates</div>
                    <form method="post" action="{{ route('commissions.rule') }}" class="form-grid">
                        @csrf
                        <div class="field"><label class="label">Collection rate %</label><input class="input" type="number" step="0.0001" min="0" name="collection_rate_percent" value="{{ old('collection_rate_percent', $rule?->collection_rate_percent ?? 3.5) }}" required></div>
                        <div class="field"><label class="label">Target bonus %</label><input class="input" type="number" step="0.0001" min="0" name="target_bonus_percent" value="{{ old('target_bonus_percent', $rule?->target_bonus_percent ?? 1) }}" required></div>
                        <div style="align-self:end"><button class="btn btn-primary" type="submit">Save rates</button></div>
                    </form>
                </div>
                <div style="text-align:right">
                    @if (($unlinkedPayments ?? 0) > 0)
                        <p class="muted" style="margin:0 0 8px;font-size:12px">{{ $unlinkedPayments }} verified payment(s) without commission</p>
                    @endif
                    <form method="post" action="{{ route('commissions.sync') }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Sync missing commissions</button>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['accrued','approved','paid','rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select class="select" name="type">
                <option value="">All types</option>
                @foreach (['collection' => 'Collection', 'target_bonus' => 'Target bonus'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="input" style="width:100px" type="number" name="year" value="{{ request('year') }}" placeholder="Year">
            <input class="input" style="width:90px" type="number" min="1" max="12" name="month" value="{{ request('month') }}" placeholder="Month">
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Commission</th>
                        <th>Salesman</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th>Base</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($commissions as $row)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $row->number }}</div>
                                @if ($row->payment)<div class="muted" style="font-size:12px">{{ $row->payment->number }}</div>@endif
                                @if ($row->order)<div class="muted" style="font-size:11px">{{ $row->order->number }}</div>@endif
                            </td>
                            <td>{{ $row->salesman?->name }}</td>
                            <td>{{ $row->typeLabel() }}</td>
                            <td class="muted">{{ $row->periodLabel() }}</td>
                            <td>{{ \App\Support\DemoData::taka($row->base_amount) }}</td>
                            <td>{{ number_format((float) $row->rate_percent, 2) }}%</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($row->commission_amount) }}</td>
                            <td><x-badge :status="$row->statusLabel()" /></td>
                            <td style="white-space:nowrap">
                                @can('commissions.approve')
                                    @if ($row->status === 'accrued')
                                        <form method="post" action="{{ route('commissions.approve', $row) }}" style="display:inline">@csrf<button class="btn btn-ghost" type="submit" style="font-size:12px">Approve</button></form>
                                        <details style="display:inline">
                                            <summary class="btn btn-ghost" style="font-size:12px;color:#b91c1c;cursor:pointer;list-style:none">Reject</summary>
                                            <form method="post" action="{{ route('commissions.reject', $row) }}" style="margin-top:6px">
                                                @csrf
                                                <input class="input" name="reason" required placeholder="Reason">
                                                <button class="btn btn-ghost" type="submit" style="color:#b91c1c;margin-top:6px">Confirm</button>
                                            </form>
                                        </details>
                                    @elseif ($row->status === 'approved')
                                        <form method="post" action="{{ route('commissions.pay', $row) }}">@csrf<button class="btn btn-ghost" type="submit" style="font-size:12px">Mark paid</button></form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted" style="padding:24px;text-align:center">No commissions yet. Verify a payment on an order/shop with a salesman, then use Sync if needed.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($commissions->hasPages())
            <div style="padding:12px 16px">{{ $commissions->links() }}</div>
        @endif
    </div>
@endsection
