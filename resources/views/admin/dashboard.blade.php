@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <div class="toolbar">
        <div class="page-kicker">
            <strong>Dashboard</strong> / Welcome back, {{ auth()->user()?->name }}
        </div>
        <span class="btn btn-ghost" style="pointer-events:none">
            <i data-lucide="calendar" style="width:16px;height:16px"></i>
            {{ now()->startOfMonth()->format('d M Y') }} – {{ now()->format('d M Y') }}
        </span>
    </div>

    @if ($pendingAudit > 0 || $pendingPayments > 0)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            @if ($pendingAudit > 0)
                <a class="btn btn-primary" href="{{ route('orders.index', ['audit_queue' => 1]) }}">Audit queue ({{ $pendingAudit }})</a>
            @endif
            @if ($pendingPayments > 0)
                <a class="btn btn-ghost" href="{{ route('payments.index', ['status' => 'pending']) }}">Payments to verify ({{ $pendingPayments }})</a>
            @endif
            <span class="muted" style="font-size:13px">{{ $openInvoices }} open invoice(s)</span>
        </div>
    @endif

    <div class="grid-5" style="margin-bottom:16px">
        @foreach ($stats as $stat)
            <div class="card stat-card">
                <div class="stat-title">{{ $stat['title'] }}</div>
                <div class="stat-value">
                    @if (!empty($stat['raw']))
                        {{ number_format($stat['value']) }}
                    @else
                        {{ \App\Support\DemoData::taka($stat['value']) }}
                    @endif
                </div>
                <div class="stat-delta {{ $stat['up'] ? 'up' : 'down' }}">
                    {{ $stat['up'] ? '↑' : '↓' }} {{ $stat['delta'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid-2" style="margin-bottom:16px">
        <div class="card" style="padding:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <div class="section-title">Sales Overview</div>
                <span class="muted">Last 14 days</span>
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
                            <div style="font-size:22px;font-weight:800">{{ array_sum($orderStatus) }}</div>
                            <div class="muted">Orders</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="legend" style="min-width:180px">
                @foreach ([
                    ['Pending Approval', $orderStatus['pending_audit'], '#f97316'],
                    ['Approved', $orderStatus['approved'], '#3b82f6'],
                    ['Processing', $orderStatus['processing'], '#8b5cf6'],
                    ['Dispatched', $orderStatus['dispatched'], '#7dd3fc'],
                    ['Delivered', $orderStatus['delivered'], '#22c55e'],
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
            @foreach ([
                ['Add Shop', 'store', '#dbeafe', '#2563eb', route('shops.create')],
                ['Create Order', 'shopping-bag', '#ffe4e6', '#e11d48', route('orders.create')],
                ['Add Product', 'package-plus', '#dcfce7', '#16a34a', route('products.create')],
                ['Add Shipment', 'ship', '#e0f2fe', '#0284c7', route('shipments.create')],
                ['Receive Stock', 'package-check', '#ccfbf1', '#0f766e', route('inventory.receive')],
                ['Generate Report', 'file-bar-chart', '#f1f5f9', '#334155', route('reports.index')],
                ['Targets', 'target', '#fef3c7', '#d97706', route('targets.index')],
                ['Analytics', 'trending-up', '#ede9fe', '#7c3aed', route('analytics.index')],
            ] as $action)
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
        <div class="card" style="padding:8px 0 0;min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 16px">
                <div class="section-title">Recent Orders</div>
                <a href="{{ route('orders.index') }}" class="muted">View all</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Shop</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td><a class="link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                                <td>{{ $order->shop?->name }}</td>
                                <td style="font-weight:700">{{ \App\Support\DemoData::taka($order->total) }}</td>
                                <td><x-badge :status="$order->statusLabel()" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted" style="padding:16px;text-align:center">No orders yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="padding:16px">
            <div class="section-title" style="margin-bottom:12px">Top stock products</div>
            @forelse ($topProducts as $product)
                <div style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid #f1f3f8">
                    <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:grid;place-items:center;color:#64748b">
                        <i data-lucide="package" style="width:18px;height:18px"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:13px">{{ $product->name }}</div>
                        <div class="muted">{{ $product->sku }} · {{ number_format($product->stock_on_hand) }} on hand</div>
                    </div>
                    <div style="font-weight:700;font-size:13px">{{ \App\Support\DemoData::taka($product->wholesale_price) }}</div>
                </div>
            @empty
                <div class="muted">No products</div>
            @endforelse
        </div>

        <div class="card" style="padding:16px">
            <div class="section-title" style="margin-bottom:12px">Shipments</div>
            @forelse ($shipments as $shipment)
                <div style="display:flex;gap:10px;align-items:center;padding:8px 0">
                    <div class="qa-ico" style="background:#e0f2fe;color:#0284c7;width:36px;height:36px">
                        <i data-lucide="ship" style="width:16px;height:16px"></i>
                    </div>
                    <div style="flex:1">
                        <a href="{{ route('shipments.show', $shipment) }}" style="font-weight:700;font-size:13px">{{ $shipment->number }}</a>
                        <div class="muted">{{ $shipment->origin ?: '—' }}</div>
                    </div>
                    <x-badge :status="$shipment->statusLabel()" />
                </div>
            @empty
                <div class="muted">No shipments</div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: @json(collect($salesSeries)->pluck('label')),
            datasets: [{
                label: 'Sales',
                data: @json(collect($salesSeries)->pluck('value')),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#3b82f6',
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { callback: v => '৳ ' + Number(v).toLocaleString('en-IN') }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('orderChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending Approval', 'Approved', 'Processing', 'Dispatched', 'Delivered'],
            datasets: [{
                data: [
                    {{ $orderStatus['pending_audit'] }},
                    {{ $orderStatus['approved'] }},
                    {{ $orderStatus['processing'] }},
                    {{ $orderStatus['dispatched'] }},
                    {{ $orderStatus['delivered'] }}
                ],
                backgroundColor: ['#f97316', '#3b82f6', '#8b5cf6', '#7dd3fc', '#22c55e'],
                borderWidth: 0,
                cutout: '72%'
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
</script>
@endpush
