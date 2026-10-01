@extends('field.layouts.app')
@section('title', 'My shops')
@section('heading', 'My shops')
@php
    $visited = $shops->where('visited_today', true)->count();
    $addedPending = $addedShops->where('status', \App\Models\Shop::STATUS_PENDING)->count();
    $addedMonth = $addedShops->filter(fn ($s) => $s->created_at?->gte(now()->startOfMonth()))->count();
@endphp
@section('subheading', $shops->count().' shops on your route · '.$visited.' visited today')
@section('actions')
    <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add shop</a>
@endsection
@section('content')
    <div class="f-kpis">
        <div class="f-kpi tone-violet">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="store"></i></span><span class="f-kpi-label">Assigned shops</span></div>
            <div class="f-kpi-value">{{ $shops->count() }}</div>
            <div class="f-kpi-meta">On your route</div>
        </div>
        <div class="f-kpi tone-green">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="check-circle-2"></i></span><span class="f-kpi-label">Visited today</span></div>
            <div class="f-kpi-value">{{ $visited }}</div>
            <div class="f-kpi-bar"><span style="width: {{ $shops->count() ? max(2, round($visited / $shops->count() * 100)) : 2 }}%"></span></div>
        </div>
        <div class="f-kpi tone-amber">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="clock"></i></span><span class="f-kpi-label">Still to visit</span></div>
            <div class="f-kpi-value">{{ $shops->count() - $visited }}</div>
            <div class="f-kpi-meta">Today</div>
        </div>
        <a class="f-kpi tone-rose" href="#added">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="store"></i></span><span class="f-kpi-label">Shops I added</span>@if ($addedPending)<span class="f-kpi-badge">{{ $addedPending }} pending</span>@endif</div>
            <div class="f-kpi-value">{{ $addedShops->count() }}</div>
            <div class="f-kpi-meta"><b>{{ $addedMonth }}</b> this month</div>
        </a>
    </div>

    <a class="f-btn f-btn-primary f-btn-block f-mobile-only" style="margin-bottom:12px" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add a new shop</a>

    <div x-data="{ q: '', show: 'all', ok(el) { const d = el.dataset; return (!this.q || d.search.includes(this.q.toLowerCase())) && (this.show === 'all' || this.show === d.state); } }">
        <div class="f-toolbar">
            <div class="f-search">
                <i data-lucide="search"></i>
                <input type="search" x-model="q" placeholder="Search shop, owner or area" autocomplete="off" aria-label="Search shops">
            </div>
            <div class="f-filters" role="tablist">
                <button type="button" class="f-filter" :class="show === 'all' && 'active'" @click="show = 'all'">All <span class="n">{{ $shops->count() }}</span></button>
                <button type="button" class="f-filter" :class="show === 'todo' && 'active'" @click="show = 'todo'">Not visited <span class="n">{{ $shops->count() - $visited }}</span></button>
                <button type="button" class="f-filter" :class="show === 'done' && 'active'" @click="show = 'done'">Visited today <span class="n">{{ $visited }}</span></button>
            </div>
        </div>

        <section class="f-panel f-desktop-only">
            <div class="f-table-wrap">
                <table class="f-table">
                    <thead><tr><th>Shop</th><th>Area</th><th>Last visit</th><th class="num">Credit left</th><th>Price group</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($shops as $shop)
                            @include('field.partials.shop-row', ['shop' => $shop, 'openVisit' => $openVisit, 'filterable' => true, 'showGroup' => true])
                        @empty
                            <tr><td colspan="6" class="empty">No shops assigned yet. Ask your manager to assign shops to your route.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="f-mobile-only">
            @forelse ($shops as $shop)
                <div x-show="ok($el.firstElementChild)">
                    @include('field.partials.shop-card', ['shop' => $shop, 'openVisit' => $openVisit])
                </div>
            @empty
                <div class="f-empty">
                    <i data-lucide="store"></i>
                    <strong>No shops assigned yet</strong>
                    Ask your manager to assign shops to your route.
                </div>
            @endforelse
        </div>
    </div>

    <section class="f-panel" id="added" style="margin-top:14px">
        <div class="f-panel-head">
            <h2><span class="f-ico tone-rose"><i data-lucide="store"></i></span>Shops I added</h2>
            <a href="{{ route('field.shops.create') }}">+ Add shop</a>
        </div>
        @if ($addedShops->isEmpty())
            <div class="f-panel-body">
                <div class="f-empty">
                    <i data-lucide="store"></i>
                    <strong>No shops added yet</strong>
                    Found a new retailer? Add them and the office will approve.
                </div>
            </div>
        @else
            <div class="f-table-wrap">
                <table class="f-table">
                    <thead><tr><th>Shop</th><th class="f-desktop-only">Area</th><th>Added</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($addedShops as $added)
                            @php
                                $tone = match ($added->status) {
                                    \App\Models\Shop::STATUS_ACTIVE => 'green',
                                    \App\Models\Shop::STATUS_REJECTED => 'red',
                                    \App\Models\Shop::STATUS_ON_HOLD => 'gray',
                                    default => 'amber',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <span class="strong">{{ $added->name }}</span>
                                    <span class="sub">{{ collect([$added->owner_name, $added->code])->filter()->implode(' · ') }}</span>
                                </td>
                                <td class="f-desktop-only">{{ $added->city ?: '—' }}</td>
                                <td class="f-muted">{{ $added->created_at?->format('d M') }}</td>
                                <td><span class="f-status {{ $tone }}">{{ $added->status === \App\Models\Shop::STATUS_PENDING ? 'Awaiting approval' : $added->statusLabel() }}</span></td>
                                <td class="num">
                                    @if ($added->users_count > 0)
                                        <span class="f-status green">Partner</span>
                                    @elseif ($added->status !== \App\Models\Shop::STATUS_REJECTED)
                                        <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.partners.create', ['shop' => $added->id]) }}" title="Apply for a partner login"><i data-lucide="handshake"></i> Partner</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
