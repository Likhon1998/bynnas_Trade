@extends('layouts.app')
@section('title', 'Returns')
@section('content')
    <x-page-header title="Returns" subtitle="Warranty, defects and dispatch corrections">
        <x-slot:description>Returns stay under review until Super Admin authorises credit, replacement or warehouse receipt.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Return No.</th>
                        <th>Shop</th>
                        <th>Order</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($returns as $row)
                        <tr>
                            <td style="font-weight:700">{{ $row['no'] }}</td>
                            <td>{{ $row['shop'] }}</td>
                            <td>{{ $row['order'] }}</td>
                            <td>{{ $row['items'] }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['amount']) }}</td>
                            <td>{{ $row['reason'] }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
