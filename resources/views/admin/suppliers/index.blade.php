@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
    <x-page-header title="Suppliers" subtitle="China and overseas sourcing partners">
        <x-slot:description>Suppliers feed purchase orders and inbound China shipments.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search supplier">
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Country</th>
                        <th>Contact</th>
                        <th>POs</th>
                        <th>Shipments</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td style="font-weight:700">{{ $supplier->code }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->country ?: '—' }}{{ $supplier->city ? ' · '.$supplier->city : '' }}</td>
                            <td>{{ $supplier->contact_name ?: $supplier->phone ?: '—' }}</td>
                            <td>{{ $supplier->purchases_count }}</td>
                            <td>{{ $supplier->shipments_count }}</td>
                            <td><x-badge :status="$supplier->is_active ? 'Active' : 'Inactive'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No suppliers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($suppliers->hasPages())
            <div style="padding:12px 16px">{{ $suppliers->links() }}</div>
        @endif
    </div>

    @can('suppliers.create')
        <form class="card" style="padding:20px" method="post" action="{{ route('suppliers.store') }}">
            @csrf
            <div style="font-weight:700;margin-bottom:12px">Add supplier</div>
            <div class="form-grid">
                <div class="field"><label class="label">Code</label><input class="input" name="code" placeholder="Auto if blank"></div>
                <div class="field"><label class="label">Name</label><input class="input" name="name" required></div>
                <div class="field"><label class="label">Country</label><input class="input" name="country" value="China"></div>
                <div class="field"><label class="label">City</label><input class="input" name="city" placeholder="Shenzhen"></div>
                <div class="field"><label class="label">Contact</label><input class="input" name="contact_name"></div>
                <div class="field"><label class="label">Phone</label><input class="input" name="phone"></div>
                <div class="field"><label class="label">Email</label><input class="input" type="email" name="email"></div>
                <div class="field"><label class="label">Payment terms</label><input class="input" name="payment_terms" placeholder="TT 30%"></div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:14px">Create supplier</button>
        </form>
    @endcan
@endsection
