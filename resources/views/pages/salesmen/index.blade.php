@extends('layouts.app')
@section('title', 'Salesmen')
@section('content')
    <x-page-header title="Salesmen" subtitle="Field force and commissions" action="{{ route('salesmen.create') }}" action-label="Add Salesman">
        <x-slot:description>Each salesman manages assigned shops, records visits and submits orders for Super Admin audit before processing.</x-slot:description>
    </x-page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Territory</th>
                        <th>Shops</th>
                        <th>Orders</th>
                        <th>Target</th>
                        <th>Achieved</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesmen as $row)
                        <tr>
                            <td><a class="link" href="{{ route('salesmen.show', $row['id']) }}">{{ $row['id'] }}</a></td>
                            <td>
                                <div style="font-weight:600">{{ $row['name'] }}</div>
                                <div class="muted">{{ $row['phone'] }}</div>
                            </td>
                            <td>{{ $row['territory'] }}</td>
                            <td>{{ $row['shops'] }}</td>
                            <td>{{ $row['orders'] }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['target']) }}</td>
                            <td>{{ \App\Support\DemoData::taka($row['achieved']) }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($row['commission']) }}</td>
                            <td><x-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
