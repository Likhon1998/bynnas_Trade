@extends('layouts.app')
@section('title', 'Add Purchase')
@section('content')
    <x-page-header title="Add Purchase" subtitle="Raise a factory PO and attach a shipment" />
    <div class="card" style="padding:22px;max-width:860px">
        <form class="form-grid" action="{{ route('purchases.index') }}" method="get">
            <div>
                <label class="label">Supplier</label>
                <select class="select" style="width:100%">
                    @foreach ($suppliers as $supplier)
                        <option>{{ $supplier['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Linked shipment</label>
                <select class="select" style="width:100%">
                    <option>Create with new shipment</option>
                    @foreach ($shipments as $shipment)
                        <option>{{ $shipment['id'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Currency</label>
                <select class="select" style="width:100%"><option>USD</option><option>CNY</option><option>BDT</option></select>
            </div>
            <div>
                <label class="label">FOB amount</label>
                <input class="input" style="width:100%" value="18400">
            </div>
            <div class="form-span">
                <label class="label">Commercial notes</label>
                <textarea class="textarea" rows="3" placeholder="Deposit, inspection, packing list reference"></textarea>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('purchases.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save purchase order</button>
            </div>
        </form>
    </div>
@endsection
