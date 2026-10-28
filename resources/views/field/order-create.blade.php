@extends('field.layouts.app')
@section('title', 'New order')
@section('heading', 'New order')
@section('subheading', 'Select any active shop from admin and order on its behalf')
@section('back', route('field.orders'))
@section('content')
    @if ($openVisit)
        <div class="f-warn" style="margin-bottom:12px">
            You have an open order at <b>{{ $openVisit->shop?->name }}</b>. Continue it, or pick another shop — the open visit will close automatically.
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.visit.show', $openVisit) }}">
                    <i data-lucide="shopping-cart"></i> Continue that order
                </a>
                <form method="post" action="{{ route('field.visit.checkout', $openVisit) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="follow_up">
                    <button class="f-btn f-btn-ghost f-btn-sm" type="submit"><i data-lucide="x"></i> Close visit</button>
                </form>
            </div>
        </div>
    @endif

    <div x-data="{ q: '' }">
        <div class="f-search">
            <i data-lucide="search"></i>
            <input type="search" x-model="q" placeholder="Search shop, owner or area" autocomplete="off" aria-label="Search shops" autofocus>
        </div>

        <p class="f-muted f-small" style="margin:0 0 10px">{{ $shops->count() }} active {{ \Illuminate\Support\Str::plural('shop', $shops->count()) }} from admin</p>

        <div class="f-mobile-only" style="display:grid;gap:10px">
            @forelse ($shops as $shop)
                @php
                    $outstanding = (float) $shop->outstanding_balance;
                    $tone = ['violet', 'blue', 'green', 'amber', 'rose', 'sky', 'teal'][crc32((string) $shop->name) % 7];
                    $search = strtolower($shop->name.' '.$shop->code.' '.$shop->owner_name.' '.$shop->city.' '.$shop->territory?->name);
                @endphp
                <div class="f-shop" data-search="{{ $search }}" x-show="!q || ($el.dataset.search || '').includes(q.toLowerCase())">
                    <div class="f-shop-head">
                        <div class="f-shop-ico tone-{{ $tone }}">{{ strtoupper(mb_substr($shop->name, 0, 1)) }}</div>
                        <div style="flex:1;min-width:0">
                            <div class="f-shop-name">{{ $shop->name }}</div>
                            <div class="f-shop-meta">{{ $shop->owner_name ?: $shop->code }}{{ $shop->city ? ' · '.$shop->city : '' }}</div>
                            <div class="f-chips">
                                <span class="f-chip {{ $outstanding > 0 ? 'red' : 'green' }}">Baki {{ \App\Support\DemoData::taka($outstanding) }}</span>
                                @if ($shop->priceGroup)<span class="f-chip">{{ $shop->priceGroup->name }}</span>@endif
                                @if ($shop->is_mine)
                                    <span class="f-chip green">My route</span>
                                @elseif ($shop->assignedSalesman)
                                    <span class="f-chip">{{ $shop->assignedSalesman->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <form method="post" action="{{ route('field.shops.order', $shop) }}" data-checkin>
                        @csrf
                        <input type="hidden" name="latitude">
                        <input type="hidden" name="longitude">
                        <button class="f-btn f-btn-primary f-btn-block" type="submit">
                            <i data-lucide="shopping-bag"></i> Order on behalf of shop
                        </button>
                    </form>
                </div>
            @empty
                <div class="f-empty">
                    <i data-lucide="store"></i>
                    <strong>No active shops</strong>
                    Ask the office to add active shops in Admin.
                </div>
            @endforelse
        </div>

        <section class="f-panel f-desktop-only">
            <div class="f-table-wrap">
                <table class="f-table">
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th>Area</th>
                            <th class="num">Baki</th>
                            <th>Price group</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shops as $shop)
                            @php
                                $outstanding = (float) $shop->outstanding_balance;
                                $tone = ['violet', 'blue', 'green', 'amber', 'rose', 'sky', 'teal'][crc32((string) $shop->name) % 7];
                                $search = strtolower($shop->name.' '.$shop->code.' '.$shop->owner_name.' '.$shop->city.' '.$shop->territory?->name);
                            @endphp
                            <tr data-search="{{ $search }}" x-show="!q || ($el.dataset.search || '').includes(q.toLowerCase())">
                                <td>
                                    <div class="who">
                                        <span class="f-av tone-{{ $tone }}">{{ strtoupper(mb_substr($shop->name, 0, 1)) }}</span>
                                        <span style="min-width:0">
                                            <span class="strong">{{ $shop->name }}</span>
                                            <span class="sub">{{ $shop->owner_name ?: $shop->code }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $shop->city ?: $shop->territory?->name ?: '—' }}</td>
                                <td class="num {{ $outstanding > 0 ? '' : 'strong' }}" style="{{ $outstanding > 0 ? 'color:var(--f-red);font-weight:700' : '' }}">{{ \App\Support\DemoData::taka($outstanding) }}</td>
                                <td>
                                    @if ($shop->priceGroup)<span class="f-chip violet">{{ $shop->priceGroup->name }}</span>@else<span class="f-muted">—</span>@endif
                                    @if ($shop->is_mine)
                                        <span class="f-chip green">My route</span>
                                    @elseif ($shop->assignedSalesman)
                                        <span class="f-chip">{{ $shop->assignedSalesman->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('field.shops.order', $shop) }}" data-checkin>
                                        @csrf
                                        <input type="hidden" name="latitude">
                                        <input type="hidden" name="longitude">
                                        <button class="f-btn f-btn-primary f-btn-sm" type="submit">
                                            <i data-lucide="shopping-bag"></i> Order
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">No active shops yet. Ask the office to add shops in Admin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
        <a class="f-btn f-btn-ghost" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add a new shop</a>
        <a class="f-btn f-btn-ghost" href="{{ route('field.shops') }}"><i data-lucide="store"></i> All shops</a>
    </div>
@endsection
