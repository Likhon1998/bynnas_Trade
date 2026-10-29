@props([
    'name' => 'phone',
    'value' => '',
    'required' => true,
    'variant' => 'site', // site | admin | field
    'invalid' => false,
])

@php
    $inputClass = match ($variant) {
        'admin' => 'input bd-phone-input',
        'field' => 'f-input bd-phone-input',
        default => 'bd-phone-input',
    };
    if ($invalid) {
        $inputClass .= ' is-invalid';
    }
    $hintClass = match ($variant) {
        'admin' => 'muted bd-phone-hint',
        'field' => 'f-muted f-small bd-phone-hint',
        default => 'site-field-hint bd-phone-hint',
    };
    $isField = $variant === 'field';
@endphp

<div
    {{ $attributes->class(['bd-phone', 'bd-phone-field' => $isField]) }}
    x-data="{
        digits: '',
        international: {{ $isField ? 'true' : 'false' }},
        init() {
            this.normalize(@js(old($name, $value)));
            this.emit();
        },
        emit() {
            this.$dispatch('bd-phone', this.digits);
        },
        normalize(v) {
            let d = String(v || '').replace(/\D/g, '');
            if (d.startsWith('880')) {
                d = d.slice(3);
            }
            if (this.international) {
                if (d.startsWith('0')) d = d.slice(1);
                d = d.slice(0, 10);
                this.digits = d ? ('0' + d) : '';
            } else {
                if (d.startsWith('0')) {
                    this.digits = d.slice(0, 11);
                } else if (d) {
                    this.digits = ('0' + d).slice(0, 11);
                } else {
                    this.digits = '';
                }
            }
        },
        get display() {
            if (this.international) {
                const local = this.digits.startsWith('0') ? this.digits.slice(1) : this.digits;
                if (!local) return '';
                if (local.length <= 4) return local;
                return local.slice(0, 4) + '-' + local.slice(4);
            }
            const d = this.digits;
            if (!d) return '';
            if (d.length <= 5) return d;
            return d.slice(0, 5) + '-' + d.slice(5);
        },
        onType(e) {
            this.normalize(e.target.value);
            this.emit();
            this.$nextTick(() => { e.target.value = this.display; });
        },
        onPaste(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.normalize(text);
            this.emit();
        },
        onKey(e) {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            const allow = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
            if (allow.includes(e.key)) return;
            if (!/^[0-9]$/.test(e.key)) e.preventDefault();
        }
    }"
>
    @if ($isField)
        <div class="bd-phone-box {{ $invalid ? 'is-invalid' : '' }}">
            <span class="bd-phone-cc" title="Bangladesh">+880</span>
            <input
                type="tel"
                class="{{ $inputClass }}"
                inputmode="numeric"
                autocomplete="tel-national"
                placeholder="1712-345678"
                maxlength="14"
                x-bind:value="display"
                @input="onType($event)"
                @paste="onPaste($event)"
                @keydown="onKey($event)"
                title="Bangladesh mobile: 11 digits, e.g. 01712-345678"
                @if ($required) required @endif
                aria-label="Mobile number"
            >
            <span class="bd-phone-count" x-text="(digits.length || 0) + '/11'" x-cloak></span>
        </div>
        <input type="hidden" name="{{ $name }}" :value="digits">
    @else
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
            @keydown="onKey($event)"
            title="Bangladesh mobile: 11 digits, e.g. 01712-345678"
            @if ($required) required @endif
            aria-label="Phone number"
        >
    @endif
    <span class="{{ $hintClass }}">
        11 digits · starts with 013–019
        <span x-show="digits.length > 0 && !international" x-cloak> · <span x-text="digits.length + '/11'"></span></span>
    </span>
</div>
