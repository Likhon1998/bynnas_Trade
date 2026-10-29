@extends('field.layouts.app')
@section('title', 'New order')
@section('heading', 'New order')
@section('subheading', 'Your shops and other shops, grouped by area')
@section('back', route('field.orders'))
@section('content')
    @php $byArea = $shops->groupBy('area_label'); @endphp
    @if ($openVisit)
        <div class="f-warn" style="margin-bottom:12px">
            You are at <b>{{ $openVisit->shop?->name }}</b>. Leave that shop first (note and photo), then start an order somewhere else.
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.visit.show', $openVisit) }}">
                    <i data-lucide="store"></i> Continue visit
                </a>
                @include('field.partials.leave-shop', [
                    'leaveVisit' => $openVisit,
                    'leaveRedirect' => 'orders',
                    'leaveClass' => 'f-btn f-btn-ghost f-btn-sm',
                ])
            </div>
        </div>
    @endif

    <div x-data="{
        q: '', show: 'all', area: '',
        ok(el) {
            const d = el.dataset;
            return (!this.q || (d.search || '').includes(this.q.toLowerCase()))
                && (this.show === 'all' || this.show === d.owner)
                && (this.area === '' || d.area === this.area);
        },
        hasVisible(section) {
            return [...section.querySelectorAll('[data-search]')].some((el) => this.ok(el));
        }
    }">
        <div class="f-toolbar">
            <div class="f-search">
                <i data-lucide="search"></i>
                <input type="search" x-model="q" placeholder="Search shop, owner or area" autocomplete="off" aria-label="Search shops" autofocus>
            </div>
            <div class="f-filters" role="tablist">
                <button type="button" class="f-filter" :class="show === 'all' && 'active'" @click="show = 'all'">All <span class="n">{{ $shops->count() }}</span></button>
                <button type="button" class="f-filter" :class="show === 'mine' && 'active'" @click="show = 'mine'">My shops <span class="n">{{ $mineCount }}</span></button>
                <button type="button" class="f-filter" :class="show === 'other' && 'active'" @click="show = 'other'">Not mine <span class="n">{{ $shops->count() - $mineCount }}</span></button>
            </div>
            @include('field.partials.area-select')
        </div>

        @forelse ($byArea as $areaName => $areaShops)
            <section class="f-area f-mobile-only" x-show="hasVisible($el)">
                <div class="f-area-head">
                    <h3><i data-lucide="map-pin"></i> {{ $areaName }}</h3>
                    <span>{{ $areaShops->count() }} {{ \Illuminate\Support\Str::plural('shop', $areaShops->count()) }}</span>
                </div>
                @foreach ($areaShops as $shop)
                    @php
                        $outstanding = (float) $shop->outstanding_balance;
                        $tone = ['violet', 'blue', 'green', 'amber', 'rose', 'sky', 'teal'][crc32((string) $shop->name) % 7];
                        $search = strtolower($shop->name.' '.$shop->code.' '.$shop->owner_name.' '.$shop->city.' '.$shop->territory?->name.' '.($shop->area_label ?? ''));
                    @endphp
                    <div class="f-shop" data-search="{{ $search }}" data-owner="{{ $shop->is_mine ? 'mine' : 'other' }}" data-area="{{ $shop->area_label }}" x-show="ok($el)">
                        <div class="f-shop-head">
                            <div class="f-shop-ico tone-{{ $tone }}">{{ strtoupper(mb_substr($shop->name, 0, 1)) }}</div>
                            <div style="flex:1;min-width:0">
                                <div class="f-shop-name">{{ $shop->name }}</div>
                                <div class="f-shop-meta">{{ $shop->owner_name ?: $shop->code }}</div>
                                <div class="f-chips">
                                    <span class="f-chip {{ $outstanding > 0 ? 'red' : 'green' }}">Baki {{ \App\Support\DemoData::taka($outstanding) }}</span>
                                    @if ($shop->is_mine)
                                        <span class="f-chip green">My shop</span>
                                    @elseif ($shop->assignedSalesman)
                                        <span class="f-chip">{{ $shop->assignedSalesman->name }}</span>
                                    @else
                                        <span class="f-chip amber">Unassigned</span>
                                    @endif
                                    @if ($shop->priceGroup)<span class="f-chip">{{ $shop->priceGroup->name }}</span>@endif
                                </div>
                            </div>
                        </div>
                        @php
                            $isOpenHere = $openVisit && (int) $openVisit->shop_id === (int) $shop->id;
                        @endphp
                        @if ($isOpenHere)
                            <a class="f-btn f-btn-warn f-btn-block" href="{{ route('field.visit.show', $openVisit) }}"><i data-lucide="store"></i> Continue visit</a>
                        @elseif ($openVisit)
                            @include('field.partials.leave-shop', [
                                'leaveVisit' => $openVisit,
                                'leaveRedirect' => 'orders',
                                'leaveClass' => 'f-btn f-btn-ghost f-btn-block',
                                'leaveLabel' => 'Leave '.$openVisit->shop?->name.' first',
                            ])
                        @elseif ($shop->is_mine)
                            <form method="post" action="{{ route('field.shops.check-in', $shop) }}" data-checkin>
                                @csrf
                                <input type="hidden" name="purpose" value="Shop visit">
                                <input type="hidden" name="latitude">
                                <input type="hidden" name="longitude">
                                <button class="f-btn f-btn-primary f-btn-block" type="submit">
                                    <i data-lucide="map-pin"></i> Visit
                                </button>
                            </form>
                        @else
                            <form method="post" action="{{ route('field.shops.order', $shop) }}" data-checkin>
                                @csrf
                                <input type="hidden" name="latitude">
                                <input type="hidden" name="longitude">
                                <button class="f-btn f-btn-primary f-btn-block" type="submit">
                                    <i data-lucide="shopping-bag"></i> Order on behalf of shop
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </section>
        @empty
            <div class="f-empty">
                <i data-lucide="store"></i>
                <strong>No active shops</strong>
                Ask the office to add shops and assign them to you.
            </div>
        @endforelse

        @if ($byArea->isNotEmpty())
            <section class="f-panel f-desktop-only">
                <div class="f-table-wrap">
                    <table class="f-table f-table-shops">
                        <thead>
                            <tr>
                                <th>Shop</th>
                                <th class="num">Baki</th>
                                <th>Who</th>
                                <th></th>
                            </tr>
                        </thead>
                        @foreach ($byArea as $areaName => $areaShops)
                            <tbody x-show="hasVisible($el)">
                                <tr class="f-area-row">
                                    <td colspan="4">
                                        <span class="f-area-row-title"><i data-lucide="map-pin"></i> {{ $areaName }}</span>
                                        <span class="f-area-row-meta">{{ $areaShops->count() }} {{ \Illuminate\Support\Str::plural('shop', $areaShops->count()) }}</span>
                                    </td>
                                </tr>
                                @foreach ($areaShops as $shop)
                                    @include('field.partials.shop-row', [
                                        'shop' => $shop,
                                        'openVisit' => $openVisit,
                                        'filterable' => true,
                                        'showGroup' => true,
                                        'hideArea' => true,
                                        'hideVisit' => true,
                                    ])
                                @endforeach
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </section>
        @endif
    </div>

    <div class="f-shop-foot">
        <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.shops.create') }}"><i data-lucide="plus"></i> Add a new shop</a>
        <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.partners.create') }}"><i data-lucide="handshake"></i> Partner application</a>
        <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.shops') }}"><i data-lucide="store"></i> All shops</a>
    </div>
@endsection
