@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
    <x-page-header title="Invoices" subtitle="Wholesale billing and credit utilisation">
        <x-slot:description>Each approved order raises an invoice against the shop ledger. Partial collections remain visible until cleared.</x-slot:description>
    </x-page-header>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Shop</th>
                        <th>Order</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Due date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $row)
                        <tr>
                            <td><a class="link" href="{{ route('invoices.show', $row['no']) }}">{{ $row['no'] }}</a></td>
                            <td>{{ $row['shop'] }}</td>
                            <td>{{ $row['order'] }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($row['amount']) }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['paid']) }}</td>
                            <td>{{ $row['due'] }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
