@extends('layouts.app')
@section('title', 'Shops')
@section('content')
    <x-page-header title="Shops" subtitle="Approved wholesale partners" action="{{ route('shops.create') }}" action-label="Add Shop">
        <x-slot:description>Shop owners receive portal credentials after Super Admin approval. Pricing, stock and credit are scoped to each shop.</x-slot:description>
    </x-page-header>

    <div class="card" style="padding:14px;margin-bottom:14px">
        <div class="filters">
            <input class="input" placeholder="Search shop, owner or city">
            <select class="select"><option>All cities</option><option>Dhaka</option><option>Chattogram</option><option>Sylhet</option></select>
            <select class="select"><option>All statuses</option><option>Active</option><option>Pending Approval</option><option>On Hold</option></select>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Shop ID</th>
                        <th>Shop</th>
                        <th>Owner</th>
                        <th>City</th>
                        <th>Assigned salesman</th>
                        <th>Credit limit</th>
                        <th>Outstanding</th>
                        <th>Orders</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shops as $shop)
                        <tr>
                            <td><a class="link" href="{{ route('shops.show', $shop['id']) }}">{{ $shop['id'] }}</a></td>
                            <td style="font-weight:600">{{ $shop['name'] }}</td>
                            <td>{{ $shop['owner'] }}</td>
                            <td>{{ $shop['city'] }}</td>
                            <td>{{ $shop['salesman'] }}</td>
                            <td>{{ \App\Support\DemoData::taka($shop['credit']) }}</td>
                            <td>{{ \App\Support\DemoData::taka($shop['outstanding']) }}</td>
                            <td>{{ $shop['orders'] }}</td>
                            <td><x-badge :status="$shop['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
