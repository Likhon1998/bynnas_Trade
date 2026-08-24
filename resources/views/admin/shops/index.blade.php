@extends('layouts.app')
@section('title', 'Shops')
@section('content')
    <x-page-header title="Shops" subtitle="Wholesale partners and portal access" action="{{ route('shops.create') }}" action-label="Add Shop">
        <x-slot:description>Approved shops receive portal credentials. Pricing and credit are scoped per shop.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search shop, owner or city">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending Approval', 'active' => 'Active', 'on_hold' => 'On Hold', 'rejected' => 'Rejected'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
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
                        <th>Price group</th>
                        <th>Salesman</th>
                        <th>Credit</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shops as $shop)
                        <tr>
                            <td><a class="link" href="{{ route('shops.show', $shop) }}">{{ $shop->code }}</a></td>
                            <td style="font-weight:600">{{ $shop->name }}</td>
                            <td>{{ $shop->owner_name }}</td>
                            <td>{{ $shop->city ?: '—' }}</td>
                            <td>{{ $shop->priceGroup?->name ?: '—' }}</td>
                            <td>{{ $shop->assignedSalesman?->name ?: '—' }}</td>
                            <td>{{ \App\Support\DemoData::taka($shop->credit_limit) }}</td>
                            <td>{{ \App\Support\DemoData::taka($shop->outstanding_balance) }}</td>
                            <td><x-badge :status="$shop->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted" style="padding:24px;text-align:center">No shops yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shops->hasPages())
            <div style="padding:14px">{{ $shops->links() }}</div>
        @endif
    </div>
@endsection
