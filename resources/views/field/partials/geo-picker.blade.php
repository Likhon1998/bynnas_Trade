<div class="as-two as-geo" @keydown.escape.window="closeGeo()">
    <div class="f-combo-backdrop" x-show="geoOpen" x-cloak @click="closeGeo()"></div>
    <p class="as-geo-status" x-show="geo.busy" x-cloak>Loading divisions…</p>
    <p class="as-geo-status is-err" x-show="geo.error" x-cloak>
        <span x-text="geo.error"></span>
        <button type="button" class="as-geo-retry" @click="loadDivisions()">Retry</button>
    </p>
    @include('field.partials.search-combo', ['key' => 'division', 'icon' => 'globe', 'label' => 'Division', 'disabled' => 'geo.busy'])
    @include('field.partials.search-combo', ['key' => 'district', 'icon' => 'map', 'label' => 'District', 'disabled' => '!f.division || geo.busyDistrict'])
    @include('field.partials.search-combo', ['key' => 'thana', 'icon' => 'map-pin', 'label' => 'Thana', 'disabled' => '!f.district || geo.busyThana'])
    @include('field.partials.search-combo', ['key' => 'post_office', 'icon' => 'mailbox', 'label' => 'Post office', 'disabled' => '!f.thana || geo.busyOffice'])
    @include('field.partials.search-combo', ['key' => 'location', 'icon' => 'navigation', 'label' => 'Location', 'disabled' => '!f.post_office', 'extraNew' => true, 'wide' => true])
    <div class="as-area-pick as-geo-wide" x-show="locationPick === '__new'" x-cloak>
        <input
            class="f-input as-area-new"
            type="text"
            maxlength="160"
            placeholder="Market, road or landmark"
            autocomplete="off"
            autocapitalize="words"
            x-ref="locationCustom"
            x-model="locationCustom"
            @input="syncLocation()"
        >
    </div>
</div>
<input type="hidden" name="division" :value="f.division">
<input type="hidden" name="district" :value="f.district">
<input type="hidden" name="thana" :value="f.thana">
<input type="hidden" name="post_office" :value="f.post_office">
<input type="hidden" name="location" :value="f.location">
<input type="hidden" name="city" :value="f.city">
