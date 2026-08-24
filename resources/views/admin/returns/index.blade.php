@extends('layouts.app')
@section('title', 'Returns')
@section('content')
    <x-page-header title="Returns" subtitle="Warranty, defect and wrong-item claims" action="{{ route('returns.create') }}" action-label="New return">
        <x-slot:description>Approved returns issue shop credit and optionally restock the warehouse.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="status">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
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
                        <th>Return</th>
                        <th>Shop</th>
                        <th>Order</th>
                        <th>Reason</th>
                        <th>Total</th>
                        <th>Restock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td><a class="link" href="{{ route('returns.show', $return) }}">{{ $return->number }}</a></td>
                            <td>{{ $return->shop?->name }}</td>
                            <td class="muted">{{ $return->order?->number ?: '—' }}</td>
                            <td>{{ $return->reasonTypeLabel() }}</td>
                            <td style="font-weight:700">{{ \App\Support\DemoData::taka($return->total) }}</td>
                            <td class="muted">{{ $return->restock ? 'Yes' : 'No' }}</td>
                            <td><x-badge :status="$return->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No returns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($returns->hasPages())
            <div style="padding:12px 16px">{{ $returns->links() }}</div>
        @endif
    </div>
@endsection
