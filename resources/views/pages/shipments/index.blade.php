@extends('layouts.app')
@section('title', 'Shipments')
@section('content')
    <x-page-header title="Shipments" subtitle="China origin to warehouse receipt" action="{{ route('shipments.create') }}" action-label="Add Shipment">
        <x-slot:description>Each inbound movement keeps product assignment, freight, customs and landed cost on a single record.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Shipment</th>
                        <th>Origin</th>
                        <th>Carrier</th>
                        <th>Items</th>
                        <th>Landed / freight</th>
                        <th>ETA</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipments as $row)
                        <tr>
                            <td><a class="link" href="{{ route('shipments.show', $row['id']) }}">{{ $row['id'] }}</a></td>
                            <td>{{ $row['origin'] }}</td>
                            <td>{{ $row['carrier'] }}</td>
                            <td>{{ number_format($row['items']) }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['cost']) }}</td>
                            <td>{{ $row['eta'] }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
