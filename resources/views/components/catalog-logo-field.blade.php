@props([
    'url' => null,
    'name' => 'logo',
    'label' => 'Logo',
])

<div class="cat-logo" x-data="catalogLogo({ url: @js($url) })">
    <label class="label">{{ $label }}</label>
    <input type="hidden" name="remove_logo" :value="removed ? 1 : 0">
    <input
        class="pg-file"
        type="file"
        name="{{ $name }}"
        accept="image/jpeg,image/png,image/webp,image/svg+xml"
        x-ref="file"
        tabindex="-1"
        aria-hidden="true"
        @change="pick($event)"
    >
    <div class="cat-logo-row">
        <button type="button" class="cat-logo-tile" @click="$refs.file.click()" :aria-label="preview ? 'Replace logo' : 'Upload logo'">
            <img x-show="preview" x-cloak :src="preview" alt="">
            <span x-show="!preview">
                <i data-lucide="image-plus"></i>
                <strong x-text="preview ? 'Replace' : 'Add logo'"></strong>
            </span>
        </button>
        <div class="cat-logo-meta">
            <button class="btn btn-ghost btn-sm" type="button" @click="$refs.file.click()" x-text="preview ? 'Replace' : 'Choose file'"></button>
            <button class="btn btn-ghost btn-sm" type="button" x-show="preview" x-cloak @click="clear()">Remove</button>
            <div class="muted" style="font-size:11px;margin-top:4px">JPG, PNG, WEBP or SVG · max 2 MB</div>
        </div>
    </div>
</div>
