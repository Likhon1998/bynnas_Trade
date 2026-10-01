@php
    $credit = $shop->availableCredit();
    $phone = preg_replace('/[^0-9+]/', '', (string) $shop->phone);
    $mapQuery = urlencode(trim(implode(', ', array_filter([$shop->name, $shop->address, $shop->city]))));
    $isOpenHere = $openVisit && $openVisit->shop_id === $shop->id;
    $tone = ['violet', 'blue', 'green', 'amber', 'rose', 'sky', 'teal'][crc32((string) $shop->name) % 7];
    $pending = $shop->status === \App\Models\Shop::STATUS_PENDING;
@endphp
<tr @if ($filterable ?? false) x-show="ok($el)" @endif
    data-search="{{ strtolower($shop->name.' '.$shop->code.' '.$shop->owner_name.' '.$shop->city.' '.$shop->territory?->name) }}"
    data-state="{{ $shop->visited_today ? 'done' : 'todo' }}">
    <td>
        <div class="who">
            <span class="f-av tone-{{ $tone }} {{ $shop->visited_today ? 'done' : '' }}">
                @if ($shop->visited_today)<i data-lucide="check"></i>@else{{ strtoupper(mb_substr($shop->name, 0, 1)) }}@endif
            </span>
            <span style="min-width:0">
                <span class="strong">{{ $shop->name }}</span>
                <span class="sub">{{ $shop->owner_name ?: $shop->code }}</span>
            </span>
        </div>
    </td>
    <td>{{ $shop->city ?: $shop->territory?->name ?: '—' }}</td>
    <td>
        @if ($isOpenHere)
            <span class="f-status amber">In progress</span>
        @elseif ($shop->visited_today)
            <span class="f-status green">Visited today</span>
        @elseif ($shop->last_visit_at)
            <span class="f-muted">{{ $shop->last_visit_at->diffForHumans(null, true) }} ago</span>
        @else
            <span class="f-status gray">Never</span>
        @endif
    </td>
    @if ($pending)
        <td class="num"><span class="f-status amber" title="Orders open once the office approves this shop">Awaiting approval</span></td>
    @else
        <td class="num {{ $credit <= 0 ? '' : 'strong' }}" style="{{ $credit <= 0 ? 'color:var(--f-red);font-weight:700' : '' }}">{{ \App\Support\DemoData::taka($credit) }}</td>
    @endif
    @if ($showGroup ?? false)
        <td>@if ($shop->priceGroup)<span class="f-chip violet">{{ $shop->priceGroup->name }}</span>@else<span class="f-muted">—</span>@endif</td>
    @endif
    <td>
        <div class="actions">
            @if ($phone)
                <a class="f-btn f-btn-ghost f-btn-sm f-btn-icon" href="tel:{{ $phone }}" title="Call {{ $shop->phone }}" aria-label="Call {{ $shop->name }}"><i data-lucide="phone"></i></a>
            @endif
            <a class="f-btn f-btn-ghost f-btn-sm f-btn-icon" href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener" title="Directions" aria-label="Directions to {{ $shop->name }}"><i data-lucide="map-pin"></i></a>
            @if ($isOpenHere)
                <a class="f-btn f-btn-warn f-btn-sm" href="{{ route('field.visit.show', $openVisit) }}"><i data-lucide="shopping-cart"></i> Continue</a>
            @elseif ($openVisit)
                <button class="f-btn f-btn-ghost f-btn-sm" type="button" disabled title="Finish your current visit first" style="opacity:.55">Check in</button>
            @else
                <form method="post" action="{{ route('field.shops.check-in', $shop) }}" data-checkin>
                    @csrf
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <button class="f-btn f-btn-primary f-btn-sm" type="submit"><i data-lucide="log-in"></i> Check in</button>
                </form>
            @endif
        </div>
    </td>
</tr>
