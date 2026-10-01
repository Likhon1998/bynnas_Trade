@extends('site.layouts.app')
@section('title', 'Become a Partner')
@section('content')
    <section class="page-hero">
        <div class="site-wrap">
            <span class="eyebrow">Become a partner</span>
            <h1>Apply for wholesale access.</h1>
            <p class="lead">Tell us about your retail business. After review, you'll receive shop portal access, your partner price list and payment terms.</p>
        </div>
    </section>

    <section class="section">
        <div class="site-wrap split split-form">
            <div class="card">
                <h2 class="card-title">Partner application</h2>
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
                        <label>
                            Phone
                            <x-bd-phone-input name="phone" :value="old('phone')" variant="site" />
                        </label>
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
                    <label>About your shop<textarea name="message" rows="4" placeholder="Product categories you sell, estimated monthly purchase, shop locations">{{ old('message') }}</textarea></label>
                    <div><button class="btn btn-primary btn-lg" type="submit">Submit application</button></div>
                </form>
            </div>

            <aside class="card card-soft">
                <h2 class="card-title">What happens next</h2>
                <ol class="next-steps">
                    <li>
                        <strong>We review your application</strong>
                        <span>Our team checks your shop details, usually within 1–2 business days.</span>
                    </li>
                    <li>
                        <strong>We contact you</strong>
                        <span>A representative calls to confirm details and discuss pricing and terms.</span>
                    </li>
                    <li>
                        <strong>You get portal access</strong>
                        <span>Log in to see your price list, place orders and manage invoices.</span>
                    </li>
                </ol>
                <p class="muted small">Questions? <a class="link" href="{{ route('site.contact') }}">Contact our team</a>.</p>
            </aside>
        </div>
    </section>
@endsection
