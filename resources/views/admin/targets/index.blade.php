@extends('layouts.app')
@section('title', 'Sales targets')
@section('content')
    <x-page-header title="Sales targets" subtitle="Monthly field targets vs delivered achievement">
        <x-slot:description>Achievement uses delivered orders attributed to the salesman. Collections drive commission and target bonus.</x-slot:description>
        @can('targets.manage')
            <form method="post" action="{{ route('targets.seed') }}">@csrf<button class="btn btn-ghost" type="submit">Seed this month from profiles</button></form>
        @endcan
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="year">
                @for ($y = now()->year; $y >= now()->year - 2; $y--)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endfor
            </select>
            <select class="select" name="month">
                @foreach ([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $m => $label)
                    <option value="{{ $m }}" @selected($month === $m)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    @can('targets.manage')
        <div class="card" style="padding:16px;margin-bottom:14px">
            <div style="font-weight:700;margin-bottom:12px">Set / update target</div>
            <form method="post" action="{{ route('targets.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label class="label">Salesman</label>
                    <select class="select" name="salesman_id" required>
                        <option value="">Select</option>
                        @foreach ($salesmen as $sm)
                            <option value="{{ $sm->id }}">{{ $sm->name }} (profile {{ \App\Support\DemoData::taka($sm->salesmanProfile?->monthly_target ?? 0) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="label">Year</label><input class="input" type="number" name="year" value="{{ $year }}" required></div>
                <div class="field"><label class="label">Month</label><input class="input" type="number" min="1" max="12" name="month" value="{{ $month }}" required></div>
                <div class="field"><label class="label">Target (৳)</label><input class="input" type="number" step="0.01" min="0" name="target_amount" required></div>
                <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit">Save target</button></div>
            </form>
        </div>
    @endcan

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Salesman</th>
                        <th>Territory</th>
                        <th>Target</th>
                        <th>Achieved</th>
                        <th>Collected</th>
                        <th>%</th>
                        <th>Met</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $pct = min(100, (int) round($row->achievementPercent())); @endphp
                        <tr>
                            <td style="font-weight:600">{{ $row->salesman?->name }}</td>
                            <td class="muted">{{ $row->territory?->name ?: $row->salesman?->salesmanProfile?->territory?->name ?: '—' }}</td>
                            <td>{{ \App\Support\DemoData::taka($row->target_amount) }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($row->achieved_amount) }}</td>
                            <td>{{ \App\Support\DemoData::taka($row->collected_amount) }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="flex:1;height:8px;background:#eef2f7;border-radius:99px;min-width:60px">
                                        <div style="height:8px;width:{{ $pct }}%;background:#ff3b30;border-radius:99px"></div>
                                    </div>
                                    <span style="font-size:12px;font-weight:700">{{ $row->achievementPercent() }}%</span>
                                </div>
                            </td>
                            <td><x-badge :status="$row->target_met ? 'Met' : 'Open'" /></td>
                            <td>
                                @can('targets.manage')
                                    <form method="post" action="{{ route('targets.recalculate', $row) }}">@csrf<button class="btn btn-ghost" type="submit" style="font-size:12px">Recalc</button></form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">No targets for this month. Seed from salesman profiles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
