@php
    $type = $field['type'];
    $id = 'web-'.$key;
    $value = in_array($type, ['image'], true) ? $value : old($key, $value);
    $error = $errors->first($key) ?: $errors->first($key.'.*') ?: $errors->first($key.'.*.title') ?: $errors->first($key.'.*.text');
    $wide = in_array($type, ['textarea', 'list', 'items', 'image', 'toggle'], true);
@endphp

<div class="web-field {{ $wide ? 'is-wide' : '' }}">
    @switch($type)
        @case('toggle')
            <input type="hidden" name="{{ $key }}" value="0">
            <label class="web-toggle" for="{{ $id }}">
                <input type="checkbox" id="{{ $id }}" name="{{ $key }}" value="1" @checked((bool) $value)>
                <span class="web-toggle-track" aria-hidden="true"></span>
                <span>{{ $field['label'] }}</span>
            </label>
            @break

        @case('textarea')
            <label class="label" for="{{ $id }}">{{ $field['label'] }}@if (! empty($field['required'])) <span class="web-req">*</span>@endif</label>
            <textarea class="textarea" id="{{ $id }}" name="{{ $key }}" rows="3" maxlength="{{ $field['max'] ?? 500 }}" @required(! empty($field['required']))>{{ $value }}</textarea>
            @break

        @case('image')
            <label class="label" for="{{ $id }}">{{ $field['label'] }}</label>
            <div class="web-image">
                @if ($url = $site->imageUrl($value))
                    <img src="{{ $url }}" alt="" class="web-image-preview {{ $key === 'logo' ? 'is-logo' : '' }}">
                @else
                    <div class="web-image-preview is-empty">{{ $key === 'logo' ? 'Default logo' : 'No image' }}</div>
                @endif
                <div class="web-image-actions">
                    <input type="file" id="{{ $id }}" name="{{ $key }}" accept="image/png,image/jpeg,image/webp" class="input">
                    @if ($value)
                        <label class="web-check"><input type="checkbox" name="remove_images[]" value="{{ $key }}"> Remove this image</label>
                    @endif
                    <span class="muted web-help">JPG, PNG or WebP, up to 4 MB.</span>
                </div>
            </div>
            @break

        @case('list')
            <label class="label">{{ $field['label'] }}</label>
            <div class="web-list" x-data="{ rows: @js(array_values((array) $value)), max: {{ (int) ($field['max_items'] ?? 10) }} }">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="web-list-row">
                        <input class="input" :name="`{{ $key }}[${i}]`" x-model="rows[i]" maxlength="160">
                        <button type="button" class="btn btn-ghost btn-sm" @click="rows.splice(i, 1)" title="Remove">✕</button>
                    </div>
                </template>
                <button type="button" class="btn btn-ghost btn-sm" @click="rows.push('')" x-show="rows.length < max">+ Add</button>
                <span class="muted web-help" x-show="rows.length >= max" x-cloak>Maximum reached.</span>
            </div>
            @break

        @case('items')
            <label class="label">{{ $field['label'] }}</label>
            <div class="web-items" x-data="{ rows: @js(array_values((array) $value)), max: {{ (int) ($field['max_items'] ?? 10) }}, move(i, d) { const j = i + d; if (j < 0 || j >= this.rows.length) return; [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]]; } }">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="web-item">
                        <div class="web-item-head">
                            <strong x-text="`#${i + 1}`"></strong>
                            <div class="web-item-tools">
                                <button type="button" class="btn btn-ghost btn-sm" @click="move(i, -1)" :disabled="i === 0" title="Move up">↑</button>
                                <button type="button" class="btn btn-ghost btn-sm" @click="move(i, 1)" :disabled="i === rows.length - 1" title="Move down">↓</button>
                                <button type="button" class="btn btn-ghost btn-sm" @click="rows.splice(i, 1)" title="Remove">✕</button>
                            </div>
                        </div>
                        <div class="web-item-body">
                            @if (! empty($field['icons']))
                                <select class="select" :name="`{{ $key }}[${i}][icon]`" x-model="row.icon">
                                    @foreach ($icons as $iconKey => $iconLabel)
                                        <option value="{{ $iconKey }}">{{ $iconLabel }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <input class="input" :name="`{{ $key }}[${i}][title]`" x-model="row.title" placeholder="Title" maxlength="120">
                            <textarea class="textarea" :name="`{{ $key }}[${i}][text]`" x-model="row.text" rows="2" placeholder="Text" maxlength="400"></textarea>
                        </div>
                    </div>
                </template>
                <button type="button" class="btn btn-ghost btn-sm" @click="rows.push({ icon: 'check', title: '', text: '' })" x-show="rows.length < max">+ Add card</button>
                <span class="muted web-help" x-show="rows.length >= max" x-cloak>Maximum reached.</span>
            </div>
            @break

        @default
            <label class="label" for="{{ $id }}">{{ $field['label'] }}@if (! empty($field['required'])) <span class="web-req">*</span>@endif</label>
            <input class="input" id="{{ $id }}" name="{{ $key }}" value="{{ $value }}"
                   type="{{ $type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text') }}"
                   maxlength="{{ $field['max'] ?? 255 }}" @required(! empty($field['required']))
                   @if ($type === 'url') placeholder="https://" @endif>
    @endswitch

    @if (! empty($field['help']))
        <span class="muted web-help">{{ $field['help'] }}</span>
    @endif
    @if ($error)
        <span class="web-error">{{ $error }}</span>
    @endif
</div>
