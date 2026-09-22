@extends('site.layouts.app')
@section('title', 'Become a Partner')
@section('content')
    <section class="site-page-hero">
        <div class="site-wrap">
            <p class="site-kicker">Become a partner</p>
            <h1 class="site-h2">Apply for wholesale shop access.</h1>
            <p class="site-lead">Tell us about your retail business. After Super Admin review you receive portal credentials, a price group and credit terms.</p>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap" style="max-width:760px">
            <div class="site-panel">
                @if (session('success'))
                    <div class="site-alert site-alert-ok">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="site-alert site-alert-err">{{ $errors->first() }}</div>
                @endif

                <form class="site-form" method="post" action="{{ route('site.partner.store') }}">
                    @csrf
                    <label>Business / shop name<input name="business_name" value="{{ old('business_name') }}" required></label>
                    <div class="site-form-row">
                        <label>Contact person<input name="contact_name" value="{{ old('contact_name') }}" required></label>
                        <label>Phone<input name="phone" value="{{ old('phone') }}"></label>
                    </div>
                    <div class="site-form-row">
                        <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
                        <label>City<input name="city" value="{{ old('city') }}"></label>
                    </div>
                    <label>Business type
                        <div
                            class="site-select"
                            x-data="{
                                open: false,
                                value: @js(old('business_type', 'retailer')),
                                options: [
                                    { value: 'retailer', label: 'Retailer' },
                                    { value: 'distributor', label: 'Distributor' },
                                    { value: 'other', label: 'Other' },
                                ],
                                get label() {
                                    return this.options.find(o => o.value === this.value)?.label || 'Select';
                                },
                                choose(v) {
                                    this.value = v;
                                    this.open = false;
                                }
                            }"
                            @keydown.escape.window="open = false"
                            @click.outside="open = false"
                        >
                            <input type="hidden" name="business_type" :value="value">
                            <button type="button" class="site-select-trigger" @click="open = !open" :aria-expanded="open">
                                <span x-text="label"></span>
                                <svg class="site-select-chevron" :class="open && 'is-open'" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <ul class="site-select-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                                <template x-for="opt in options" :key="opt.value">
                                    <li>
                                        <button
                                            type="button"
                                            class="site-select-option"
                                            :class="value === opt.value && 'is-active'"
                                            @click="choose(opt.value)"
                                            x-text="opt.label"
                                        ></button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </label>
                    <label>About your shop / volumes<textarea name="message" rows="4" placeholder="Categories you sell, monthly purchase estimate, existing locations">{{ old('message') }}</textarea></label>
                    <button class="site-cta" type="submit">Submit application</button>
                </form>
            </div>
        </div>
    </section>
@endsection
