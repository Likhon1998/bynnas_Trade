@extends('field.layouts.app')
@section('title', 'New partner application')
@section('heading', 'New partner application')
@section('subheading', 'Apply for a shop owner to join the Bynnas Trade partner portal.')
@section('back', route('field.partners'))
@section('has-bar', '1')
@php
    $shopOptions = $shops->map(fn ($s) => [
        'id' => $s->id,
        'label' => $s->name.' · '.$s->code,
        'name' => $s->name,
        'owner' => $s->owner_name,
        'phone' => $s->phone,
        'email' => $s->email,
        'city' => $s->city,
    ])->values();
    $initial = [
        'shop_id' => (string) old('shop_id', $selectedShop?->id ?? ''),
        'business_name' => old('business_name', $selectedShop?->name ?? ''),
        'contact_name' => old('contact_name', $selectedShop?->owner_name ?? ''),
        'email' => old('email', $selectedShop?->email ?? ''),
        'phone' => old('phone', $selectedShop?->phone ?? ''),
        'city' => old('city', $selectedShop?->city ?? ''),
        'business_type' => old('business_type', 'retailer'),
    ];
@endphp
@section('actions')
    <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.partners') }}">Cancel</a>
    <button class="f-btn f-btn-primary f-btn-sm" type="submit" form="partner-form"><i data-lucide="send"></i> Send application</button>
@endsection
@section('content')
    <form id="partner-form" method="post" action="{{ route('field.partners.store') }}" class="f-split as-form"
          x-data="partnerForm(@js($initial), @js($shopOptions))" @submit="saving = true">
        @csrf
        <div class="as-main">
            <section class="f-panel as-sec">
                <div class="as-sec-head"><span class="f-ico tone-blue"><i data-lucide="store"></i></span><div><h2>Which shop?</h2><p>Pick one of yours — or apply for a new business</p></div></div>
                <div class="as-sec-body">
                    <select class="f-input @error('shop_id') is-invalid @enderror" name="shop_id" x-model="f.shop_id" @change="pick()">
                        <option value="">A new business (not in my shops)</option>
                        @foreach ($shopOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('shop_id')<span class="f-error">{{ $message }}</span>@enderror
                    @if ($shops->isEmpty())
                        <p class="f-muted f-small" style="margin:6px 0 0">All your shops already have a partner login.</p>
                    @endif
                </div>
            </section>

            <div class="as-cols">
                <div class="as-col">
                    <section class="f-panel as-sec">
                        <div class="as-sec-head"><span class="f-ico tone-violet"><i data-lucide="building-2"></i></span><div><h2>Business</h2><p>As it should appear on invoices</p></div></div>
                        <div class="as-sec-body" style="display:grid;gap:10px">
                            <label class="as-field">
                                <span class="as-label">Business name <em class="as-req">*</em></span>
                                <input class="f-input as-big @error('business_name') is-invalid @enderror" name="business_name" x-model="f.business_name" maxlength="180" required placeholder="e.g. Rahim Telecom" autocapitalize="words">
                                @error('business_name')<span class="f-error">{{ $message }}</span>@enderror
                            </label>
                            <div>
                                <span class="as-label">Type of business</span>
                                <div class="as-chips">
                                    @foreach ($businessTypes as $key => $label)
                                        <label class="as-chip">
                                            <input type="radio" name="business_type" value="{{ $key }}" x-model="f.business_type">
                                            <span><i data-lucide="check"></i>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <label class="as-field">
                                <span class="as-label">Area / city</span>
                                <input class="f-input @error('city') is-invalid @enderror" name="city" x-model="f.city" maxlength="80" placeholder="e.g. Mirpur 10" autocapitalize="words">
                                @error('city')<span class="f-error">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    </section>
                </div>

                <div class="as-col">
                    <section class="f-panel as-sec">
                        <div class="as-sec-head"><span class="f-ico tone-teal"><i data-lucide="key-round"></i></span><div><h2>Owner &amp; login</h2><p>The portal login goes to this email</p></div></div>
                        <div class="as-sec-body" style="display:grid;gap:10px">
                            <label class="as-field">
                                <span class="as-label">Owner name</span>
                                <input class="f-input @error('contact_name') is-invalid @enderror" name="contact_name" x-model="f.contact_name" maxlength="120" placeholder="e.g. Abdur Rahim" autocapitalize="words">
                                @error('contact_name')<span class="f-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="as-field">
                                <span class="as-label">Owner email <em class="as-req">*</em></span>
                                <span class="as-prefix">
                                    <i data-lucide="mail"></i>
                                    <input class="f-input @error('email') is-invalid @enderror" name="email" x-model="f.email" type="email" inputmode="email" autocapitalize="off" spellcheck="false" maxlength="180" required placeholder="owner@example.com">
                                </span>
                                @error('email')<span class="f-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="as-field">
                                <span class="as-label">Owner mobile <em class="as-req">*</em></span>
                                <span class="as-prefix">
                                    <i data-lucide="phone"></i>
                                    <input class="f-input @error('phone') is-invalid @enderror" name="phone" x-model="f.phone" type="tel" inputmode="tel" maxlength="20" required placeholder="01XXXXXXXXX">
                                </span>
                                @error('phone')<span class="f-error">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    </section>
                </div>
            </div>

            <section class="f-panel as-sec">
                <div class="as-sec-body">
                    <label class="as-field">
                        <span class="as-label">Note for the office</span>
                        <textarea class="f-textarea @error('message') is-invalid @enderror" name="message" rows="2" maxlength="2000" placeholder="Monthly volume, brands they want, credit they asked for…">{{ old('message') }}</textarea>
                        @error('message')<span class="f-error">{{ $message }}</span>@enderror
                    </label>
                </div>
            </section>
        </div>

        <aside class="as-side">
            <section class="f-panel as-preview">
                <div class="as-preview-top">
                    <div class="as-av" style="background:linear-gradient(135deg,#14B8A6,#0F766E)" x-text="(f.business_name || 'P').trim().charAt(0).toUpperCase()"></div>
                    <div style="min-width:0">
                        <b x-text="f.business_name || 'New partner'"></b>
                        <span x-text="f.shop_id ? 'Existing shop — will be upgraded' : 'New business'"></span>
                    </div>
                </div>
                <ol class="f-howto" style="margin-top:12px">
                    <li><b>You send the application</b>It goes to the office right away.</li>
                    <li><b>The office accepts it</b>They set prices and credit for the shop.</li>
                    <li><b>The owner gets a login</b>Sent to their email and WhatsApp — then they can order online.</li>
                </ol>
                <button class="f-btn f-btn-primary f-btn-lg f-btn-block" type="submit" :disabled="saving">
                    <i data-lucide="send"></i> <span x-text="saving ? 'Sending…' : 'Send application'"></span>
                </button>
                <p class="as-foot"><i data-lucide="info"></i> Fields marked * are needed to create the owner's portal login.</p>
            </section>
        </aside>

        <div class="f-cartbar as-bar">
            <div class="f-cartbar-text">
                <small x-text="f.shop_id ? 'Existing shop' : 'New business'"></small>
                <strong x-text="f.business_name || 'New partner'"></strong>
            </div>
            <button class="f-btn f-btn-success" type="submit" :disabled="saving">
                <i data-lucide="send"></i> <span x-text="saving ? 'Sending…' : 'Send'"></span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
function partnerForm(initial, shops) {
    return {
        f: initial,
        shops,
        saving: false,
        pick() {
            const s = this.shops.find((x) => String(x.id) === String(this.f.shop_id));
            if (!s) return;
            this.f.business_name = s.name || '';
            this.f.contact_name = s.owner || '';
            this.f.phone = s.phone || '';
            this.f.email = s.email || this.f.email;
            this.f.city = s.city || '';
        },
    };
}
</script>
@endpush
