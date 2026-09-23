@props([
    'name' => 'phone',
    'value' => '',
    'required' => true,
    'variant' => 'site', // site | admin
])

@php
    $inputClass = $variant === 'admin' ? 'input bd-phone-input' : 'bd-phone-input';
    $hintClass = $variant === 'admin' ? 'muted bd-phone-hint' : 'site-field-hint bd-phone-hint';
@endphp

<div
    {{ $attributes->class('bd-phone') }}
    x-data="{
        digits: '',
        init() {
            this.normalize(@js(old($name, $value)));
        },
        normalize(v) {
            let d = String(v || '').replace(/\D/g, '');
            if (d.startsWith('880') && d.length >= 13) {
                d = '0' + d.slice(3);
            }
            this.digits = d.slice(0, 11);
        },
        get display() {
            const d = this.digits;
            if (!d) return '';
            if (d.length <= 5) return d;
            return d.slice(0, 5) + '-' + d.slice(5);
        },
        onType(e) {
            this.normalize(e.target.value);
            this.$nextTick(() => { e.target.value = this.display; });
        },
        onPaste(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.normalize(text);
        }
    }"
>
    <input type="hidden" name="{{ $name }}" :value="digits">
    <input
        type="tel"
        class="{{ $inputClass }}"
        style="{{ $variant === 'admin' ? 'width:100%' : '' }}"
        inputmode="numeric"
        autocomplete="tel-national"
        placeholder="01712-345678"
        maxlength="12"
        x-bind:value="display"
        @input="onType($event)"
        @paste="onPaste($event)"
        title="Bangladesh mobile: 11 digits, e.g. 01712-345678"
        @if ($required) required @endif
        aria-label="Phone number"
    >
    <span class="{{ $hintClass }}">
        Type 11 digits · formats as <strong style="font-weight:600">01XXX-XXXXXX</strong>
        <span x-show="digits.length > 0" x-cloak> · <span x-text="digits.length + '/11'"></span></span>
    </span>
</div>
