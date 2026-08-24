@extends('layouts.app')
@section('title', 'Rewards')
@section('content')
    <x-page-header title="Rewards" subtitle="Target-hit and manual field rewards">
        <x-slot:description>Target-hit rewards are created automatically when a salesman meets the monthly target. Approve / pay from here.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    @can('rewards.manage')
        <div class="card" style="padding:16px;margin-bottom:14px">
            <div style="font-weight:700;margin-bottom:12px">Create reward</div>
            <form method="post" action="{{ route('rewards.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label class="label">Salesman</label>
                    <select class="select" name="salesman_id" required>
                        <option value="">Select</option>
                        @foreach ($salesmen as $sm)
                            <option value="{{ $sm->id }}">{{ $sm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="label">Title</label><input class="input" name="title" required value="{{ old('title') }}"></div>
                <div class="field">
                    <label class="label">Type</label>
                    <select class="select" name="type">
                        <option value="manual">Manual</option>
                        <option value="top_performer">Top performer</option>
                        <option value="target_hit">Target hit</option>
                    </select>
                </div>
                <div class="field"><label class="label">Year</label><input class="input" type="number" name="year" value="{{ old('year', now()->year) }}" required></div>
                <div class="field"><label class="label">Month</label><input class="input" type="number" min="1" max="12" name="month" value="{{ old('month', now()->month) }}" required></div>
                <div class="field"><label class="label">Amount (৳)</label><input class="input" type="number" step="0.01" min="0" name="amount" required value="{{ old('amount') }}"></div>
                <div class="field" style="grid-column:1/-1"><label class="label">Notes</label><textarea class="input" name="notes" rows="2">{{ old('notes') }}</textarea></div>
                <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit">Create</button></div>
            </form>
        </div>
    @endcan

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['pending','awarded','paid','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
                        <th>Reward</th>
                        <th>Salesman</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rewards as $row)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $row->number }}</div>
                                <div class="muted" style="font-size:12px">{{ $row->title }}</div>
                            </td>
                            <td>{{ $row->salesman?->name }}</td>
                            <td>{{ $row->typeLabel() }}</td>
                            <td class="muted">{{ $row->periodLabel() }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($row->amount) }}</td>
                            <td><x-badge :status="$row->statusLabel()" /></td>
                            <td>
                                @can('rewards.approve')
                                    @if ($row->status === 'pending')
                                        <form method="post" action="{{ route('rewards.award', $row) }}" style="display:inline">@csrf<button class="btn btn-ghost" type="submit" style="font-size:12px">Award</button></form>
                                    @elseif ($row->status === 'awarded')
                                        <form method="post" action="{{ route('rewards.pay', $row) }}" style="display:inline">@csrf<button class="btn btn-ghost" type="submit" style="font-size:12px">Mark paid</button></form>
                                    @endif
                                @endcan
                                @can('rewards.manage')
                                    @if (in_array($row->status, ['pending', 'awarded'], true))
                                        <details style="display:inline">
                                            <summary class="btn btn-ghost" style="font-size:12px;color:#b91c1c;cursor:pointer;list-style:none">Cancel</summary>
                                            <form method="post" action="{{ route('rewards.cancel', $row) }}" style="margin-top:6px">
                                                @csrf
                                                <input class="input" name="reason" required placeholder="Reason">
                                                <button class="btn btn-ghost" type="submit" style="color:#b91c1c;margin-top:6px">Confirm</button>
                                            </form>
                                        </details>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No rewards yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rewards->hasPages())
            <div style="padding:12px 16px">{{ $rewards->links() }}</div>
        @endif
    </div>
@endsection
