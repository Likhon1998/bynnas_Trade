@extends('layouts.app')
@section('title', 'Purchases')
@section('content')
    <x-page-header title="Purchases" subtitle="Factory orders linked to shipments" action="{{ route('purchases.create') }}" action-label="Add Purchase">
        <x-slot:description>Purchase orders feed the shipment they travel on. Receiving posts stock and finalises landed cost.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>PO No.</th>
                        <th>Supplier</th>
                        <th>Shipment</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $row)
                        <tr>
                            <td style="font-weight:700">{{ $row['no'] }}</td>
                            <td>{{ $row['supplier'] }}</td>
                            <td>{{ $row['shipment'] }}</td>
                            <td>{{ number_format($row['items']) }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['amount']) }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
