@extends('layouts.app')
@section('title', 'Analytics')
@section('content')
    <x-page-header title="Analytics" subtitle="Trading, credit and field performance">
        <x-slot:description>Live trends from orders, invoices, payments and salesman targets.</x-slot:description>
    </x-page-header>

    <div class="grid-4" style="margin-bottom:16px">
        @foreach ($kpis as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:22px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
                <div class="muted">{{ $card[2] }}</div>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Commission this month</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($commissionMonth) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Returns this month</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($returnsMonth) }}</div></div>
    </div>

    <div class="grid-2">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:8px">Billed vs collected</div>
            <canvas id="cashChart" height="140"></canvas>
        </div>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">Territory / salesman targets</div>
            @forelse ($targets as $row)
                @php $pct = min(100, (int) round($row->achievementPercent())); @endphp
                <div style="margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;font-size:13px">
                        <span>{{ $row->salesman?->name }} · {{ $row->territory?->name ?: '—' }}</span>
                        <strong>{{ $pct }}%</strong>
                    </div>
                    <div style="height:8px;background:#eef2f7;border-radius:99px;margin-top:6px">
                        <div style="height:8px;width:{{ $pct }}%;background:#a855f7;border-radius:99px"></div>
                    </div>
                    <div class="muted" style="font-size:12px;margin-top:4px">
                        {{ \App\Support\DemoData::taka($row->achieved_amount) }} / {{ \App\Support\DemoData::taka($row->target_amount) }}
                    </div>
                </div>
            @empty
                <div class="muted">No targets for this month. Seed from Targets.</div>
            @endforelse
        </div>
    </div>
@endsection
@push('scripts')
<script>
    new Chart(document.getElementById('cashChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($billedVsCollected)->pluck('label')),
            datasets: [
                { label: 'Billed', data: @json(collect($billedVsCollected)->pluck('billed')), backgroundColor: '#3b82f6', borderRadius: 6 },
                { label: 'Collected', data: @json(collect($billedVsCollected)->pluck('collected')), backgroundColor: '#22c55e', borderRadius: 6 },
            ]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { ticks: { callback: v => '৳ ' + Number(v).toLocaleString('en-IN') }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
