@php
    $areas = collect($areas ?? []);
@endphp
<div class="as-area-pick">
    <div class="f-area-select as-area-select">
        <i data-lucide="map-pin"></i>
        <select x-model="cityPick" aria-label="Area / city" class="@error('city') is-invalid @enderror">
            <option value="">Select area</option>
            @foreach ($areas as $area)
                <option value="{{ $area }}">{{ $area }}</option>
            @endforeach
            <option value="__new">+ Add a new area</option>
        </select>
        <i data-lucide="chevron-down"></i>
    </div>
    <input
        class="f-input as-area-new @error('city') is-invalid @enderror"
        type="text"
        maxlength="80"
        placeholder="Type the new area name"
        autocomplete="off"
        autocapitalize="words"
        x-show="cityPick === '__new'"
        x-cloak
        x-model="cityCustom"
        x-ref="cityCustom"
        @keydown.enter.prevent
    >
    <input type="hidden" name="city" :value="f.city">
</div>
