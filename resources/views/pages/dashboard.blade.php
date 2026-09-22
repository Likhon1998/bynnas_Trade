@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="toolbar">
        <div class="page-kicker">
            <strong>Dashboard</strong> / Welcome back, Super Admin 👋
        </div>
        <button class="btn btn-ghost" type="button">
            <i data-lucide="calendar" style="width:16px;height:16px"></i>
            22 May 2025 – 28 May 2025
        </button>
    </div>

    <div class="grid-5" style="margin-bottom:16px">
        @php
            $stats = [
                ['title' => 'Total Sales (This Month)', 'value' => '৳ 12,45,000', 'delta' => '18.5% vs last month', 'up' => true, 'color' => '#6D28D9', 'points' => '0,28 20,22 40,24 60,12 80,16 100,8 120,14'],
                ['title' => 'Total Orders', 'value' => '1,248', 'delta' => '12.4% vs last month', 'up' => true, 'color' => '#3b82f6', 'points' => '0,24 20,20 40,18 60,16 80,10 100,12 120,6'],
                ['title' => 'Total Shops', 'value' => '832', 'delta' => '8.1% vs last month', 'up' => true, 'color' => '#8b5cf6', 'points' => '0,26 20,24 40,20 60,18 80,14 100,15 120,10'],
                ['title' => 'Outstanding Amount', 'value' => '৳ 8,75,320', 'delta' => '6.3% vs last month', 'up' => false, 'color' => '#f97316', 'points' => '0,10 20,12 40,16 60,14 80,20 100,18 120,24'],
                ['title' => 'Total Products', 'value' => '2,145', 'delta' => '5.7% vs last month', 'up' => true, 'color' => '#14b8a6', 'points' => '0,22 20,20 40,18 60,14 80,16 100,11 120,8'],
            ];
        @endphp
        @foreach ($stats as $stat)
            <div class="card stat-card">
                <div class="stat-title">{{ $stat['title'] }}</div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-delta {{ $stat['up'] ? 'up' : 'down' }}">
                    {{ $stat['up'] ? '↑' : '↓' }} {{ $stat['delta'] }}
                </div>
                <svg viewBox="0 0 120 32" width="100%" height="36" preserveAspectRatio="none" style="margin-top:8px">
                    <polyline fill="none" stroke="{{ $stat['color'] }}" stroke-width="2.4" stroke-linecap="round" points="{{ $stat['points'] }}"/>
                </svg>
            </div>
        @endforeach
    </div>

    <div class="grid-2" style="margin-bottom:16px">
        <div class="card" style="padding:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <div class="section-title">Sales Overview</div>
                <span class="muted">Wholesale billed this week</span>
            </div>
            <canvas id="salesChart" height="120"></canvas>
        </div>
        <div class="card" style="padding:18px;display:flex;gap:18px;align-items:center">
            <div style="flex:1">
                <div class="section-title" style="margin-bottom:12px">Order Status</div>
                <div style="position:relative;max-width:210px;margin:0 auto">
                    <canvas id="orderChart" height="210"></canvas>
                    <div style="position:absolute;inset:0;display:grid;place-items:center;pointer-events:none">
                        <div style="text-align:center">
                            <div style="font-size:22px;font-weight:800">1,248</div>
                            <div class="muted">Orders</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="legend" style="min-width:180px">
                @foreach ([
                    ['Pending Approval', 245, '#f97316'],
                    ['Approved', 310, '#3b82f6'],
                    ['Processing', 210, '#8b5cf6'],
                    ['Shipped', 280, '#7dd3fc'],
                    ['Delivered', 203, '#22c55e'],
                ] as $row)
                    <div style="display:flex;justify-content:space-between;gap:12px">
                        <span><span class="swatch" style="background:{{ $row[2] }}"></span> {{ $row[0] }}</span>
                        <strong>{{ $row[1] }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card" style="padding:18px;margin-bottom:16px">
        <div class="section-title" style="margin-bottom:12px">Quick Actions</div>
        <div class="qa-grid">
            @php
                $actions = [
                    ['Add Shop', 'store', '#dbeafe', '#2563eb', route('shops.create')],
                    ['Add Salesman', 'user-plus', '#f3e8ff', '#6D28D9', route('salesmen.create')],
                    ['Create Order', 'shopping-bag', '#ffe4e6', '#e11d48', route('orders.create')],
                    ['Add Product', 'package-plus', '#dcfce7', '#16a34a', route('products.create')],
                    ['Add Shipment', 'ship', '#e0f2fe', '#0284c7', route('shipments.create')],
                    ['Add Purchase', 'clipboard-plus', '#ffedd5', '#ea580c', route('purchases.create')],
                    ['Receive Stock', 'package-check', '#ccfbf1', '#0f766e', route('inventory.receive')],
                    ['Generate Report', 'file-bar-chart', '#f1f5f9', '#334155', route('reports.index')],
                ];
            @endphp
            @foreach ($actions as $action)
                <a class="qa" href="{{ $action[4] }}">
                    <span class="qa-ico" style="background:{{ $action[2] }};color:{{ $action[3] }}">
                        <i data-lucide="{{ $action[1] }}"></i>
                    </span>
                    {{ $action[0] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid-3">
        <div class="card" style="padding:8px 0 0;grid-column: span 1; min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 16px">
                <div class="section-title">Recent Orders</div>
                <a href="{{ route('orders.index') }}" class="muted">View all</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Order No.</th>
                            <th>Shop</th>
                            <th>Salesman</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td><a class="link" href="{{ route('orders.show', $order['no']) }}">{{ $order['no'] }}</a></td>
                                <td>{{ $order['shop'] }}</td>
                                <td>{{ $order['salesman'] }}</td>
                                <td style="font-weight:700">{{ \App\Support\DemoData::taka($order['amount']) }}</td>
                                <td><x-badge :status="$order['status']" /></td>
                                <td class="muted">{{ $order['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="padding:16px">
            <div class="section-title" style="margin-bottom:12px">Top Selling Products</div>
            @foreach ($products as $i => $product)
                @if ($i > 3) @continue @endif
                <div style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid #f1f3f8">
                    <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:grid;place-items:center;color:#64748b">
                        <i data-lucide="package" style="width:18px;height:18px"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:13px">{{ $product['name'] }}</div>
                        <div class="muted">{{ number_format($product['stock']) }} Units</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:700;font-size:13px">{{ \App\Support\DemoData::taka($product['price'] * 80) }}</div>
                        <div class="up" style="font-size:12px">+{{ [18,12,9,7][$i] }}%</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card" style="padding:16px">
                <div class="section-title" style="margin-bottom:12px">Shipment Tracking</div>
                @foreach ($shipments as $shipment)
                    <div style="display:flex;gap:10px;align-items:center;padding:8px 0">
                        <div class="qa-ico" style="background:#e0f2fe;color:#0284c7;width:36px;height:36px">
                            <i data-lucide="ship" style="width:16px;height:16px"></i>
                        </div>
                        <div style="flex:1">
                            <a href="{{ route('shipments.show', $shipment['id']) }}" style="font-weight:700;font-size:13px">{{ $shipment['id'] }}</a>
                            <div class="muted">From: {{ $shipment['origin'] }}</div>
                        </div>
                        <x-badge :status="$shipment['status']" />
                    </div>
                @endforeach
            </div>
            <div class="card" style="padding:16px">
                <div class="section-title" style="margin-bottom:12px">Recent Activities</div>
                @foreach ($activities as $activity)
                    <div style="display:flex;gap:10px;padding:8px 0">
                        <div class="qa-ico" style="background:#f3e8ff;color:#6D28D9;width:32px;height:32px">
                            <i data-lucide="activity" style="width:14px;height:14px"></i>
                        </div>
                        <div>
                            <div style="font-size:13px">{{ $activity['text'] }}</div>
                            <div class="muted">{{ $activity['time'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const salesCtx = document.getElementById('salesChart');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['22 May', '23 May', '24 May', '25 May', '26 May', '27 May', '28 May'],
            datasets: [{
                label: 'Sales',
                data: [98000, 112000, 121000, 135000, 118000, 129000, 142000],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6',
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { callback: v => '৳ ' + v.toLocaleString('en-IN') }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('orderChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending Approval', 'Approved', 'Processing', 'Shipped', 'Delivered'],
            datasets: [{
                data: [245, 310, 210, 280, 203],
                backgroundColor: ['#f97316', '#3b82f6', '#8b5cf6', '#7dd3fc', '#22c55e'],
                borderWidth: 0,
                cutout: '72%'
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
</script>
@endpush
