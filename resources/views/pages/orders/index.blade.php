@extends('layouts.app')
@section('title', 'Orders')
@section('content')
    <x-page-header title="Orders" subtitle="Audit, approve and fulfil wholesale orders" action="{{ route('orders.create') }}" action-label="Create Order">
        <x-slot:description>Shop portal and salesman orders both wait in Super Admin audit before stock is reserved.</x-slot:description>
    </x-page-header>

    <div class="card" style="padding:14px;margin-bottom:14px">
        <div class="filters">
            <input class="input" placeholder="Search order, shop or salesman">
            <select class="select"><option>All sources</option><option>Shop Portal</option><option>Salesman</option></select>
            <select class="select"><option>All statuses</option><option>Pending Approval</option><option>Approved</option><option>Processing</option><option>Shipped</option><option>Delivered</option></select>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Order No.</th>
                        <th>Shop</th>
                        <th>Salesman</th>
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a class="link" href="{{ route('orders.show', $order['no']) }}">{{ $order['no'] }}</a></td>
                            <td>{{ $order['shop'] }}</td>
                            <td>{{ $order['salesman'] }}</td>
                            <td><x-badge :status="$order['source']" /></td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($order['amount']) }}</td>
                            <td><x-badge :status="$order['status']" /></td>
                            <td class="muted">{{ $order['date'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
