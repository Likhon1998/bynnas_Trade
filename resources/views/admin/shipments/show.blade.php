@extends('layouts.app')
@section('title', $shipment->number)
@section('content')
    <x-page-header title="{{ $shipment->number }}" subtitle="From {{ $shipment->origin }} · {{ $shipment->statusLabel() }}">
        <a class="btn btn-ghost" href="{{ route('shipments.index') }}">Back</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Carrier</div><div style="font-weight:700">{{ $shipment->carrier ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Supplier</div><div style="font-weight:700">{{ $shipment->supplier?->name ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Goods value</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($shipment->goods_value_bdt) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Extra costs</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($shipment->extraCostsTotal()) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Total landed</div><div style="font-weight:800;color:#6D28D9">{{ \App\Support\DemoData::taka($shipment->total_landed_cost) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">ETA</div><div style="font-weight:700">{{ $shipment->eta_at?->format('d M Y') ?: '—' }}</div></div>
    </div>

    @if ($shipment->status !== \App\Models\Shipment::STATUS_RECEIVED)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            @can('shipments.edit')
                <form class="card" style="padding:16px" method="post" action="{{ route('shipments.costs', $shipment) }}">
                    @csrf
                    <div style="font-weight:700;margin-bottom:10px">Landed cost inputs (BDT)</div>
                    <div class="form-grid">
                        <div class="field"><label class="label">Freight</label><input class="input" type="number" step="0.01" name="freight_cost" value="{{ $shipment->freight_cost }}"></div>
                        <div class="field"><label class="label">Customs</label><input class="input" type="number" step="0.01" name="customs_duty" value="{{ $shipment->customs_duty }}"></div>
                        <div class="field"><label class="label">Insurance</label><input class="input" type="number" step="0.01" name="insurance_cost" value="{{ $shipment->insurance_cost }}"></div>
                        <div class="field"><label class="label">Other</label><input class="input" type="number" step="0.01" name="other_cost" value="{{ $shipment->other_cost }}"></div>
                    </div>
                    <button class="btn btn-ghost" type="submit" style="margin-top:12px">Recalculate allocation</button>
                </form>
            @endcan

            <div class="card" style="padding:16px">
                <div style="font-weight:700;margin-bottom:10px">Warehouse actions</div>
                @can('shipments.edit')
                    @if ($shipment->status !== \App\Models\Shipment::STATUS_ARRIVED)
                        <form method="post" action="{{ route('shipments.arrive', $shipment) }}" style="margin-bottom:10px">@csrf
                            <button class="btn btn-ghost" type="submit">Mark arrived</button>
                        </form>
                    @endif
                @endcan
                @can('shipments.receive')
                    <form method="post" action="{{ route('shipments.receive', $shipment) }}">
                        @csrf
                        <div class="field" style="margin-bottom:10px">
                            <label class="label">Receive into</label>
                            <select class="select" name="warehouse_id">
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected($shipment->warehouse_id == $warehouse->id || $warehouse->is_default)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Receive & update stock + landed cost</button>
                    </form>
                @endcan
            </div>
        </div>
    @else
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">
            Received {{ $shipment->received_at?->format('d M Y H:i') }} into {{ $shipment->warehouse?->name }} by {{ $shipment->receiver?->name }}.
        </div>
    @endif

    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Products on this shipment</div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Goods value</th>
                        <th>Allocated extras</th>
                        <th>Unit landed</th>
                        <th>Line landed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipment->items as $item)
                        <tr>
                            <td>{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ \App\Support\DemoData::taka($item->line_goods_value) }}</td>
                            <td>{{ \App\Support\DemoData::taka($item->allocated_extra_cost) }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($item->unit_landed_cost) }}</td>
                            <td>{{ \App\Support\DemoData::taka($item->line_landed_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
