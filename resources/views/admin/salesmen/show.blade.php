@extends('layouts.app')
@section('title', $salesman->name)
@section('content')
    <x-page-header title="{{ $salesman->name }}" subtitle="{{ $salesman->salesmanProfile?->employee_code }} · Field force">
        <a class="btn btn-ghost" href="{{ route('salesmen.edit', $salesman) }}">Edit</a>
        <a class="btn btn-ghost" href="{{ route('salesmen.index') }}">Back</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:14px">
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Territory</div><div style="font-weight:700">{{ $salesman->salesmanProfile?->territory?->name ?: '—' }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Assigned shops</div><div style="font-weight:700">{{ $salesman->assignedShops->count() }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">Monthly target</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($salesman->salesmanProfile?->monthly_target ?? 0) }}</div></div>
        <div class="card" style="padding:14px"><div class="muted" style="font-size:12px">This month collected</div><div style="font-weight:700">{{ \App\Support\DemoData::taka($monthOrders) }}</div></div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:14px">
        <div style="font-weight:700;margin-bottom:8px">Field login</div>
        <div class="muted">URL: <a class="link" href="{{ url('/field/login') }}">{{ url('/field/login') }}</a></div>
        <div class="muted">Email: {{ $salesman->email }}</div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="card">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Assigned shops</div>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        @forelse ($salesman->assignedShops as $shop)
                            <tr>
                                <td><a class="link" href="{{ route('shops.show', $shop) }}">{{ $shop->code }}</a></td>
                                <td>{{ $shop->name }}</td>
                                <td class="muted">{{ $shop->city }}</td>
                            </tr>
                        @empty
                            <tr><td class="muted" style="padding:16px">No shops assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Recent orders</div>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        @forelse ($salesman->salesmanOrders as $order)
                            <tr>
                                <td><a class="link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                                <td>{{ $order->shop?->name }}</td>
                                <td>{{ \App\Support\DemoData::taka($order->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="muted" style="padding:16px">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
