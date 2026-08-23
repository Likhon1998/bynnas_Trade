@extends('layouts.app')
@section('title', 'Payments')
@section('content')
    <x-page-header title="Payments" subtitle="Collections, cheques and mobile money">
        <x-slot:description>Salesman collections and bank transfers wait for clearance before outstanding balances and commissions update.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Shop</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $row)
                        <tr>
                            <td style="font-weight:700">{{ $row['id'] }}</td>
                            <td>{{ $row['shop'] }}</td>
                            <td>{{ $row['invoice'] }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['amount']) }}</td>
                            <td>{{ $row['method'] }}</td>
                            <td>{{ $row['ref'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
