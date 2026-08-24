@extends('layouts.app')
@section('title', 'Purchases')
@section('content')
    <x-page-header title="Purchases" subtitle="Purchase orders to China suppliers" action="{{ route('purchases.create') }}" action-label="Create PO">
        <x-slot:description>POs convert into China shipments with freight and customs for landed cost.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="PO # or supplier">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['draft','ordered','partial','received','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
                        <th>PO</th>
                        <th>Supplier</th>
                        <th>Currency</th>
                        <th>Value (BDT)</th>
                        <th>Ordered</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td><a class="link" href="{{ route('purchases.show', $purchase) }}">{{ $purchase->number }}</a></td>
                            <td>{{ $purchase->supplier?->name }}</td>
                            <td>{{ $purchase->currency }} @ {{ $purchase->exchange_rate }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($purchase->subtotal_bdt) }}</td>
                            <td class="muted">{{ $purchase->ordered_at?->format('d M Y') }}</td>
                            <td><x-badge :status="$purchase->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">No purchase orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($purchases->hasPages())
            <div style="padding:12px 16px">{{ $purchases->links() }}</div>
        @endif
    </div>
@endsection
