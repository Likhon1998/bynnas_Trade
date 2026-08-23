@extends('layouts.app')
@section('title', 'Receive Stock')
@section('content')
    <x-page-header title="Receive Stock" subtitle="Post shipment arrival into warehouse" />
    <div class="card" style="padding:22px;max-width:860px">
        <form class="form-grid" action="{{ route('inventory.index') }}" method="get">
            <div>
                <label class="label">Shipment</label>
                <select class="select" style="width:100%">
                    @foreach ($shipments as $shipment)
                        <option>{{ $shipment['id'] }} — {{ $shipment['status'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Receiving warehouse</label>
                <select class="select" style="width:100%">
                    @foreach ($warehouses as $warehouse)
                        <option>{{ $warehouse['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Received date</label>
                <input class="input" style="width:100%" type="date" value="2025-05-28">
            </div>
            <div>
                <label class="label">Condition</label>
                <select class="select" style="width:100%"><option>Good — as packed</option><option>Partial shortage</option><option>Damaged cartons</option></select>
            </div>
            <div class="form-span">
                <label class="label">Receiving note</label>
                <textarea class="textarea" rows="3" placeholder="Customs release, carton count, discrepancy"></textarea>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('inventory.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Post to inventory</button>
            </div>
        </form>
    </div>
@endsection
