@php
    $key = $key ?? 'item';
    $icon = $icon ?? 'map-pin';
    $label = $label ?? 'Select';
    $disabled = $disabled ?? 'false';
    $error = $error ?? $key;
    $extraNew = ! empty($extraNew);
    $wide = ! empty($wide);
@endphp
<label class="as-field {{ $wide ? 'as-geo-wide' : '' }}">
    <span class="as-label">{{ $label }}</span>
    <div class="f-combo" :class="{ 'is-open': geoOpen === '{{ $key }}', 'is-disabled': {{ $disabled }} }" @click.outside="geoOpen === '{{ $key }}' && closeGeo()">
        <div class="f-area-select as-area-select">
            <i data-lucide="{{ $icon }}"></i>
            <button
                type="button"
                class="f-combo-btn"
                :disabled="{{ $disabled }}"
                :aria-expanded="geoOpen === '{{ $key }}'"
                aria-haspopup="listbox"
                aria-label="{{ $label }}"
                @click="toggleGeo('{{ $key }}')"
            >
                <span class="f-combo-value" :class="{ muted: !geoValue('{{ $key }}') }" x-text="geoLabel('{{ $key }}')"></span>
            </button>
            <i data-lucide="chevron-down"></i>
        </div>
        <div class="f-combo-pop" x-show="geoOpen === '{{ $key }}'" x-cloak @click.stop role="dialog" aria-label="{{ $label }}">
            <div class="f-combo-sheet-head">
                <span class="f-sheet-grip" aria-hidden="true"></span>
                <div class="f-combo-sheet-bar">
                    <strong>{{ $label }}</strong>
                    <button type="button" class="f-combo-done" @click="closeGeo()">Done</button>
                </div>
            </div>
            <div class="f-combo-search">
                <i data-lucide="search"></i>
                <input
                    type="search"
                    x-ref="geoSearch{{ $key }}"
                    x-model="geoQuery"
                    :placeholder="'Search {{ strtolower($label) }}'"
                    autocomplete="off"
                    enterkeyhint="search"
                    @keydown="onGeoKey($event, '{{ $key }}')"
                >
            </div>
            <ul class="f-combo-list" role="listbox">
                <template x-for="(item, i) in filteredGeo('{{ $key }}')" :key="item.value">
                    <li>
                        <button
                            type="button"
                            role="option"
                            :class="{ on: item.value === geoValue('{{ $key }}'), hi: i === geoHi }"
                            @click="pickGeo('{{ $key }}', item.value)"
                            @mouseenter="geoHi = i"
                            x-text="item.label"
                        ></button>
                    </li>
                </template>
                <li x-show="filteredGeo('{{ $key }}').length === 0">
                    <span class="f-combo-empty">No match</span>
                </li>
                @if ($extraNew)
                    <li>
                        <button type="button" class="f-combo-new" @click="pickGeo('{{ $key }}', '__new')">+ Add a new location</button>
                    </li>
                @endif
            </ul>
        </div>
    </div>
    @error($error)<span class="f-error">{{ $message }}</span>@enderror
</label>
