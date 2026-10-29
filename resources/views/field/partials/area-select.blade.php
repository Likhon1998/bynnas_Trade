@if ($areaCounts->isNotEmpty())
    @php
        $areaOptions = collect($areaCounts)->map(fn ($n, $areaName) => [
            'value' => (string) $areaName,
            'label' => $areaName.' ('.$n.')',
        ])->values()->all();
        array_unshift($areaOptions, [
            'value' => '',
            'label' => 'All areas ('.$shops->count().')',
        ]);
    @endphp
    <div
        class="f-combo"
        x-data="searchCombo({ items: @js($areaOptions), placeholder: 'All areas' })"
        x-modelable="value"
        x-model="area"
        @click.outside="open = false"
        :class="{ 'is-open': open }"
    >
        <div class="f-area-select">
            <i data-lucide="map-pin"></i>
            <button type="button" class="f-combo-btn" @click="toggle()" aria-label="Filter by area" :aria-expanded="open">
                <span class="f-combo-value" :class="{ muted: !value }" x-text="display"></span>
            </button>
            <i data-lucide="chevron-down"></i>
        </div>
        <div class="f-combo-backdrop" x-show="open" x-cloak @click="open = false"></div>
        <div class="f-combo-pop" x-show="open" x-cloak @click.stop role="dialog" aria-label="Filter by area">
            <div class="f-combo-sheet-head">
                <span class="f-sheet-grip" aria-hidden="true"></span>
                <div class="f-combo-sheet-bar">
                    <strong>Area</strong>
                    <button type="button" class="f-combo-done" @click="open = false">Done</button>
                </div>
            </div>
            <div class="f-combo-search">
                <i data-lucide="search"></i>
                <input type="search" x-ref="query" x-model="query" placeholder="Search area" autocomplete="off" inputmode="search" enterkeyhint="search" @keydown="onKey($event)">
            </div>
            <ul class="f-combo-list" role="listbox">
                <template x-for="(item, i) in filtered" :key="item.value || 'all'">
                    <li>
                        <button type="button" :class="{ on: item.value === value, hi: i === hi }" @click="pick(item.value)" @mouseenter="hi = i" x-text="item.label"></button>
                    </li>
                </template>
                <li x-show="filtered.length === 0"><span class="f-combo-empty">No match</span></li>
            </ul>
        </div>
    </div>
@endif
