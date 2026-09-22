@extends('layouts.app')
@section('title', 'Analytics')
@section('content')
    <x-page-header title="Analytics" subtitle="Trading, credit and field performance">
        <x-slot:description>Trends for Super Admin: sales, margin, outstanding, inbound shipments and salesman achievement.</x-slot:description>
    </x-page-header>
    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([['Gross margin', '28.4%', 'On wholesale billed'], ['Fill rate', '94.1%', 'Lines shipped complete'], ['Credit at risk', '৳ 3,12,000', 'Over 30 days'], ['Target hit', '3 / 4', 'Active salesmen']] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:22px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
                <div class="muted">{{ $card[2] }}</div>
            </div>
        @endforeach
    </div>
    <div class="grid-2">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:8px">Billed vs collected</div>
            <canvas id="cashChart" height="140"></canvas>
        </div>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">Territory contribution</div>
            @foreach ($salesmen as $row)
                @php $pct = min(100, (int) round($row['achieved'] / $row['target'] * 100)); @endphp
                <div style="margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;font-size:13px">
                        <span>{{ $row['name'] }} · {{ $row['territory'] }}</span>
                        <strong>{{ $pct }}%</strong>
                    </div>
                    <div style="height:8px;background:#eef2f7;border-radius:99px;margin-top:6px">
                        <div style="height:8px;width:{{ $pct }}%;background:#6D28D9;border-radius:99px"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
@push('scripts')
<script>
    new Chart(document.getElementById('cashChart'), {
        type: 'bar',
        data: {
            labels: ['22 May', '23 May', '24 May', '25 May', '26 May', '27 May', '28 May'],
            datasets: [
                { label: 'Billed', data: [98000,112000,121000,135000,118000,129000,142000], backgroundColor: '#3b82f6', borderRadius: 6 },
                { label: 'Collected', data: [42000,88000,61000,120000,54000,75000,40000], backgroundColor: '#22c55e', borderRadius: 6 },
            ]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { ticks: { callback: v => '৳ ' + v.toLocaleString('en-IN') }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
