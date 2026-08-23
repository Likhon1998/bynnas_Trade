@extends('layouts.app')
@section('title', 'Products')
@section('content')
    <x-page-header title="Products" subtitle="Catalogue, wholesale price and landed cost" action="{{ route('products.create') }}" action-label="Add Product">
        <x-slot:description>Every SKU stays linked to the shipment it arrived on so landed cost, warranty and stock remain auditable.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>On hand</th>
                        <th>Reserved</th>
                        <th>Wholesale</th>
                        <th>Landed cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td style="font-weight:700">{{ $product['sku'] }}</td>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ $product['category'] }}</td>
                            <td>{{ number_format($product['stock']) }}</td>
                            <td>{{ $product['reserved'] }}</td>
                            <td>{{ \App\Support\DemoData::taka($product['price']) }}</td>
                            <td>{{ \App\Support\DemoData::taka($product['landed']) }}</td>
                            <td><x-badge :status="$product['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
