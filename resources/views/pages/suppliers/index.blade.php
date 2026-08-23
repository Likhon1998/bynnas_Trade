@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
    <x-page-header title="Suppliers" subtitle="China factories and trading houses">
        <x-slot:description>Procurement stays attached to the supplier, purchase order and shipment so origin is never lost.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Terms</th>
                        <th>Open POs</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $row)
                        <tr>
                            <td style="font-weight:700">{{ $row['id'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['contact'] }}</td>
                            <td>{{ $row['terms'] }}</td>
                            <td>{{ $row['open_pos'] }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
