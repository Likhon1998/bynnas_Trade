@props([
    'name',
    'label',
    'noun',
    'options',
    'selected' => '',
    'storeUrl',
    'canCreate' => false,
])

@php
    $list = collect($options)->map(fn ($item) => [
        'id' => is_array($item) ? $item['id'] : $item->id,
        'name' => is_array($item) ? $item['name'] : $item->name,
    ])->values();
    $selectedValue = $selected !== null && $selected !== '' ? (string) $selected : '';
@endphp

<div
    class="catalog-quick"
    x-data="quickCatalogSelect({
        selected: @js($selectedValue),
        options: @js($list),
        storeUrl: @js($storeUrl),
        canCreate: @js((bool) $canCreate),
        label: @js($noun),
    })"
>
    <div class="catalog-quick-row">
        <div class="catalog-quick-field">
            <label class="label">{{ $label }}</label>
            <select class="select" style="width:100%" name="{{ $name }}" x-model="selected" @change="onChange()">
                <option value="">Select</option>
                <template x-for="opt in options" :key="'opt-'+opt.id">
                    <option :value="String(opt.id)" x-text="opt.name"></option>
                </template>
                @if ($canCreate)
                    <option value="__new__">+ Add new {{ $noun }}…</option>
                @endif
            </select>
        </div>
        @if ($canCreate)
            <button class="btn btn-ghost catalog-quick-add-btn" type="button" @click="startAdd()" title="Add a new {{ $noun }}">
                + New
            </button>
        @endif
    </div>
    <div class="catalog-quick-form" x-show="adding" x-cloak>
        <input class="input" style="flex:1" x-model="newName" @keydown.enter.prevent="create()" placeholder="New {{ $noun }} name">
        <input class="pg-file" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" x-ref="logo" @change="pickLogo($event)">
        <button class="cat-logo-mini" type="button" @click="$refs.logo.click()" :title="'Optional ' + label + ' logo'">
            <img x-show="logoPreview" x-cloak :src="logoPreview" alt="">
            <span x-show="!logoPreview">Logo</span>
        </button>
        <button class="btn btn-ghost btn-sm" type="button" x-show="logoPreview" x-cloak @click="clearLogo()">✕</button>
        <button class="btn btn-primary btn-sm" type="button" @click="create()" :disabled="saving" x-text="saving ? 'Saving…' : 'Save'"></button>
        <button class="btn btn-ghost btn-sm" type="button" @click="cancel()">Cancel</button>
    </div>
    <p class="catalog-quick-error" x-show="error" x-text="error"></p>
</div>
