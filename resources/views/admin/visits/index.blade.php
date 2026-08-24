@extends('layouts.app')
@section('title', 'Shop Visits')
@section('content')
    <x-page-header title="Shop Visits" subtitle="Field check-ins and outcomes">
        <a class="btn btn-ghost" href="{{ route('salesmen.index') }}">Salesmen</a>
    </x-page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Salesman</th>
                        <th>Shop</th>
                        <th>Purpose</th>
                        <th>Outcome</th>
                        <th>Order</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td>{{ $visit->checked_in_at?->format('d M Y H:i') }}</td>
                            <td>{{ $visit->salesman?->name }}</td>
                            <td>{{ $visit->shop?->name }} <span class="muted">({{ $visit->shop?->code }})</span></td>
                            <td>{{ $visit->purpose ?: '—' }}</td>
                            <td><x-badge :status="$visit->outcomeLabel()" /></td>
                            <td>
                                @if ($visit->order)
                                    <a class="link" href="{{ route('orders.show', $visit->order) }}">{{ $visit->order->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="muted">
                                @if ($visit->checked_out_at)
                                    {{ $visit->checked_in_at->diffForHumans($visit->checked_out_at, true) }}
                                @else
                                    Open
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">No visits recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())
            <div style="padding:12px 16px">{{ $visits->links() }}</div>
        @endif
    </div>
@endsection
