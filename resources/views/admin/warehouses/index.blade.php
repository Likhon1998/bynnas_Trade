@extends('layouts.app')
@section('title', 'Warehouses')
@section('content')
    <x-page-header title="Warehouses" subtitle="Stock locations for pick, pack and dispatch">
        <x-slot:description>Approved orders reserve stock at the default warehouse, then move through the fulfilment queue.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="margin-bottom:14px">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>City</th>
                        <th>SKUs</th>
                        <th>Default</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td style="font-weight:700">{{ $warehouse->code }}</td>
                            <td>{{ $warehouse->name }}</td>
                            <td>{{ $warehouse->city ?: '—' }}</td>
                            <td>{{ $warehouse->stocks_count }}</td>
                            <td>{{ $warehouse->is_default ? 'Yes' : '—' }}</td>
                            <td><x-badge :status="$warehouse->is_active ? 'Active' : 'Inactive'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">No warehouses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('warehouses.create')
        <form class="card" style="padding:20px" method="post" action="{{ route('warehouses.store') }}">
            @csrf
            <div style="font-weight:700;margin-bottom:12px">Add warehouse</div>
            <div class="form-grid">
                <div class="field"><label class="label">Code</label><input class="input" name="code" required placeholder="WH-DHK"></div>
                <div class="field"><label class="label">Name</label><input class="input" name="name" required></div>
                <div class="field"><label class="label">City</label><input class="input" name="city"></div>
                <div class="field"><label class="label">Phone</label><input class="input" name="phone"></div>
                <div class="field" style="grid-column:1/-1"><label class="label">Address</label><textarea class="input" name="address" rows="2"></textarea></div>
                <div class="field"><label style="display:flex;gap:8px;align-items:center;font-size:13px"><input type="checkbox" name="is_default" value="1"> Default warehouse</label></div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:14px">Create warehouse</button>
        </form>
    @endcan
@endsection
