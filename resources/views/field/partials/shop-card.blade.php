@php
    $credit = $shop->availableCredit();
    $phone = preg_replace('/[^0-9+]/', '', (string) $shop->phone);
    $mapQuery = urlencode(trim(implode(', ', array_filter([$shop->name, $shop->address, $shop->city]))));
    $isOpenHere = $openVisit && $openVisit->shop_id === $shop->id;
    $tone = ['violet', 'blue', 'green', 'amber', 'rose', 'sky', 'teal'][crc32((string) $shop->name) % 7];
@endphp
<div class="f-shop {{ $shop->visited_today && ! $isOpenHere ? 'done' : '' }}" data-search="{{ strtolower($shop->name.' '.$shop->code.' '.$shop->owner_name.' '.$shop->city.' '.$shop->territory?->name) }}" data-state="{{ $shop->visited_today ? 'done' : 'todo' }}">
    <div class="f-shop-head">
        <div class="f-shop-ico tone-{{ $tone }} {{ $shop->visited_today ? 'done' : '' }}">
            @if ($shop->visited_today)
                <i data-lucide="check"></i>
            @else
                {{ strtoupper(mb_substr($shop->name, 0, 1)) }}
            @endif
        </div>
        <div style="flex:1;min-width:0">
            <div class="f-shop-name">{{ $shop->name }}</div>
            <div class="f-shop-meta">{{ $shop->owner_name }}{{ $shop->city || $shop->territory ? ' · '.($shop->city ?: $shop->territory?->name) : '' }}</div>
            <div class="f-chips">
                @if ($isOpenHere)
                    <span class="f-chip amber">Visit in progress</span>
                @elseif ($shop->visited_today)
                    <span class="f-chip green">Visited today</span>
                @elseif ($shop->last_visit_at)
                    <span class="f-chip">Last visit {{ $shop->last_visit_at->diffForHumans(null, true) }} ago</span>
                @else
                    <span class="f-chip amber">Never visited</span>
                @endif
                @if ($shop->status === \App\Models\Shop::STATUS_PENDING)
                    <span class="f-chip amber">Awaiting approval</span>
                @else
                    <span class="f-chip {{ $credit <= 0 ? 'red' : '' }}">Credit {{ \App\Support\DemoData::taka($credit) }}</span>
                @endif
                @if ($shop->priceGroup)
                    <span class="f-chip">{{ $shop->priceGroup->name }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="f-shop-actions">
        @if ($phone)
            <a class="f-btn f-btn-ghost f-btn-icon" href="tel:{{ $phone }}" aria-label="Call {{ $shop->owner_name ?: $shop->name }}"><i data-lucide="phone"></i></a>
        @endif
        <a class="f-btn f-btn-ghost f-btn-icon" href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener" aria-label="Directions to {{ $shop->name }}"><i data-lucide="map-pin"></i></a>
        @if ($isOpenHere)
            <a class="f-btn f-btn-warn" style="flex:1" href="{{ route('field.visit.show', $openVisit) }}"><i data-lucide="shopping-cart"></i> Continue order</a>
        @elseif ($openVisit)
            <button class="f-btn f-btn-ghost" style="flex:1" type="button" disabled title="Finish your current visit first">Finish current visit first</button>
        @else
            <form method="post" action="{{ route('field.shops.check-in', $shop) }}" data-checkin>
                @csrf
                <input type="hidden" name="latitude">
                <input type="hidden" name="longitude">
                <button class="f-btn f-btn-primary" type="submit"><i data-lucide="log-in"></i> Check in</button>
            </form>
        @endif
    </div>
</div>
