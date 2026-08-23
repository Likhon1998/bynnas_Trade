@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
    <x-page-header title="Inventory" subtitle="Live stock across warehouses" action="{{ route('inventory.receive') }}" action-label="Receive Stock">
        <x-slot:description>On-hand, reserved and available quantities stay in sync with Super Admin approvals and warehouse picking.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>On hand</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td style="font-weight:700">{{ $product['sku'] }}</td>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ number_format($product['stock']) }}</td>
                            <td>{{ $product['reserved'] }}</td>
                            <td style="font-weight:700">{{ number_format($product['stock'] - $product['reserved']) }}</td>
                            <td>Dhaka Central Warehouse</td>
                            <td><x-badge :status="$product['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
