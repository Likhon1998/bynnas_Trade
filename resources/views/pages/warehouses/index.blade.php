@extends('layouts.app')
@section('title', 'Warehouses')
@section('content')
    <x-page-header title="Warehouses" subtitle="Bonded storage and distribution hubs">
        <x-slot:description>Picking, packing and dispatch run from the warehouse assigned after Super Admin approval.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Warehouse</th>
                        <th>Location</th>
                        <th>SKUs</th>
                        <th>Capacity</th>
                        <th>Manager</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($warehouses as $row)
                        <tr>
                            <td style="font-weight:700">{{ $row['id'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['location'] }}</td>
                            <td>{{ $row['skus'] }}</td>
                            <td>{{ $row['capacity'] }}</td>
                            <td>{{ $row['manager'] }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
