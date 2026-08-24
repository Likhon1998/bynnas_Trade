@extends('layouts.app')
@section('title', $purchase->number)
@section('content')
    <x-page-header title="{{ $purchase->number }}" subtitle="{{ $purchase->supplier?->name }} · {{ $purchase->statusLabel() }}">
        <a class="btn btn-ghost" href="{{ route('purchases.index') }}">Back</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Supplier</div><div style="font-weight:700">{{ $purchase->supplier?->name }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Foreign total</div><div style="font-weight:700">{{ $purchase->currency }} {{ number_format($purchase->subtotal_foreign, 2) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">BDT total</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($purchase->subtotal_bdt) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">FX rate</div><div style="font-weight:700">{{ $purchase->exchange_rate }}</div></div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Lines</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Received</th><th>Unit (BDT)</th><th>Line (BDT)</th></tr></thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td>{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->received_quantity }}</td>
                            <td>{{ \App\Support\DemoData::taka($item->unit_cost_bdt) }}</td>
                            <td style="font-weight:600">{{ \App\Support\DemoData::taka($item->line_total_bdt) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @can('shipments.create')
        @if (in_array($purchase->status, ['ordered', 'partial'], true))
            <form class="card" style="padding:20px;margin-bottom:14px" method="post" action="{{ route('purchases.shipment', $purchase) }}">
                @csrf
                <div style="font-weight:700;margin-bottom:12px">Create China shipment from this PO</div>
                <div class="form-grid">
                    <div class="field"><label class="label">Origin</label><input class="input" name="origin" value="Shenzhen, China"></div>
                    <div class="field"><label class="label">Carrier</label><input class="input" name="carrier" placeholder="COSCO / Maersk"></div>
                    <div class="field"><label class="label">Tracking</label><input class="input" name="tracking_ref"></div>
                    <div class="field"><label class="label">Container</label><input class="input" name="container_no"></div>
                    <div class="field"><label class="label">ETA</label><input class="input" type="date" name="eta_at"></div>
                    <div class="field"><label class="label">Freight (BDT)</label><input class="input" type="number" step="0.01" min="0" name="freight_cost" value="0"></div>
                    <div class="field"><label class="label">Customs (BDT)</label><input class="input" type="number" step="0.01" min="0" name="customs_duty" value="0"></div>
                    <div class="field"><label class="label">Insurance (BDT)</label><input class="input" type="number" step="0.01" min="0" name="insurance_cost" value="0"></div>
                    <div class="field"><label class="label">Other (BDT)</label><input class="input" type="number" step="0.01" min="0" name="other_cost" value="0"></div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:14px">Create shipment</button>
            </form>
        @endif
    @endcan

    @if ($purchase->shipments->isNotEmpty())
        <div class="card">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Linked shipments</div>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        @foreach ($purchase->shipments as $shipment)
                            <tr>
                                <td><a class="link" href="{{ route('shipments.show', $shipment) }}">{{ $shipment->number }}</a></td>
                                <td>{{ $shipment->statusLabel() }}</td>
                                <td>{{ \App\Support\DemoData::taka($shipment->total_landed_cost) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
