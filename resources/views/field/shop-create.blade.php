@extends('field.layouts.app')
@section('title', 'Add a shop')
@section('heading', 'Add a shop')
@section('subheading', 'Shop + owner login in one step — then you can take orders.')
@section('back', route('field.shops'))
@section('has-bar', '1')
@php
    $territory = auth()->user()->salesmanProfile?->territory?->name;
    $initial = [
        'name' => old('name', ''),
        'owner' => old('owner_name', ''),
        'phone' => old('phone', ''),
        'city' => old('city', $territory ?? ''),
        'address' => old('address', ''),
        'sells' => array_values((array) old('sells', [])),
        'interest' => old('interest', ''),
        'createLogin' => (bool) old('create_login', true),
        'loginEmail' => old('login_email', ''),
        'loginPassword' => old('login_password', $suggestedPassword),
    ];
@endphp
@section('actions')
    <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.shops') }}">Cancel</a>
    <button class="f-btn f-btn-primary f-btn-sm" type="submit" form="add-shop"><i data-lucide="check"></i> Save shop</button>
@endsection
@section('content')
    <form id="add-shop" method="post" action="{{ route('field.shops.store') }}" class="f-split as-form"
          x-data="addShop(@js($initial))" x-init="locate()" @submit="saving = true">
        @csrf
        <input type="hidden" name="latitude" :value="lat ?? ''">
        <input type="hidden" name="longitude" :value="lng ?? ''">

        <div class="as-main">
            {{-- Location --}}
            <section class="as-loc" :class="state">
                <span class="as-loc-ico">
                    <span x-show="state !== 'ok'"><i data-lucide="map-pin"></i></span>
                    <span x-show="state === 'ok'" x-cloak><i data-lucide="check"></i></span>
                </span>
                <div class="as-loc-text">
                    <b x-text="state === 'ok' ? 'Location saved' : (state === 'err' ? 'Couldn\'t get your location' : 'Finding your location…')"></b>
                    <span x-show="state === 'ok'" x-cloak x-text="lat?.toFixed(5) + ', ' + lng?.toFixed(5) + (accuracy ? ' · within ' + accuracy + ' m' : '')"></span>
                    <span x-show="state === 'err'" x-cloak>Turn on GPS and tap retry — or save without it.</span>
                    <span x-show="state === 'busy'">Stand at the shop for the best result.</span>
                </div>
                <div class="as-loc-actions">
                    <a x-show="state === 'ok'" x-cloak class="f-btn f-btn-ghost f-btn-sm" :href="'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng" target="_blank" rel="noopener"><i data-lucide="map"></i><span class="as-hide-xs">Map</span></a>
                    <button type="button" class="f-btn f-btn-ghost f-btn-sm" @click="locate()" :disabled="state === 'busy'"><i data-lucide="refresh-cw"></i><span class="as-hide-xs">Retry</span></button>
                </div>
            </section>

            <div class="as-cols">
            <div class="as-col">
            {{-- Shop --}}
            <section class="f-panel as-sec">
                <div class="as-sec-head"><span class="f-ico tone-violet"><i data-lucide="store"></i></span><div><h2>Shop</h2><p>Name on the signboard</p></div></div>
                <div class="as-sec-body">
                    <label class="as-field">
                        <span class="as-label">Shop name</span>
                        <input class="f-input as-big @error('name') is-invalid @enderror" name="name" x-model="f.name" maxlength="160" placeholder="e.g. Rahim Telecom" autocomplete="off" autocapitalize="words" enterkeyhint="next">
                        @error('name')<span class="f-error">{{ $message }}</span>@enderror
                    </label>

                    <div class="as-label" style="margin-top:14px">What do they sell?</div>
                    <div class="as-chips">
                        @foreach ($categories as $category)
                            <label class="as-chip">
                                <input type="checkbox" name="sells[]" value="{{ $category }}" x-model="f.sells">
                                <span><i data-lucide="check"></i>{{ $category }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Owner --}}
            <section class="f-panel as-sec">
                <div class="as-sec-head"><span class="f-ico tone-blue"><i data-lucide="user-round"></i></span><div><h2>Owner &amp; contact</h2><p>Who to call for orders</p></div></div>
                <div class="as-sec-body as-two">
                    <label class="as-field">
                        <span class="as-label">Owner name</span>
                        <input class="f-input @error('owner_name') is-invalid @enderror" name="owner_name" x-model="f.owner" maxlength="120" placeholder="e.g. Abdur Rahim" autocomplete="off" autocapitalize="words" enterkeyhint="next">
                        @error('owner_name')<span class="f-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="as-field">
                        <span class="as-label">Mobile number</span>
                        <span class="as-prefix">
                            <i data-lucide="phone"></i>
                            <input class="f-input @error('phone') is-invalid @enderror" name="phone" x-model="f.phone" type="tel" inputmode="tel" maxlength="20" placeholder="01XXXXXXXXX" autocomplete="off" enterkeyhint="next">
                        </span>
                        @error('phone')<span class="f-error">{{ $message }}</span>@enderror
                    </label>
                </div>
            </section>
            </div>

            <div class="as-col">

            {{-- Address --}}
            <section class="f-panel as-sec">
                <div class="as-sec-head"><span class="f-ico tone-teal"><i data-lucide="navigation"></i></span><div><h2>Address</h2><p>Helps the delivery team find it</p></div></div>
                <div class="as-sec-body as-two">
                    <label class="as-field">
                        <span class="as-label">Area / city</span>
                        <input class="f-input @error('city') is-invalid @enderror" name="city" x-model="f.city" maxlength="80" placeholder="e.g. Mirpur 10" autocomplete="off" autocapitalize="words" enterkeyhint="next">
                        @error('city')<span class="f-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="as-field">
                        <span class="as-label">Market / road / landmark</span>
                        <input class="f-input @error('address') is-invalid @enderror" name="address" x-model="f.address" maxlength="255" placeholder="e.g. Shah Ali Plaza, 2nd floor" autocomplete="off" enterkeyhint="next">
                        @error('address')<span class="f-error">{{ $message }}</span>@enderror
                    </label>
                </div>
            </section>

            {{-- Owner login --}}
            <section class="f-panel as-sec">
                <div class="as-sec-head"><span class="f-ico tone-green"><i data-lucide="key-round"></i></span><div><h2>Owner login</h2><p>So they can order online later</p></div></div>
                <div class="as-sec-body">
                    <label class="as-visit" style="margin:0 0 12px;border-style:dashed">
                        <input type="hidden" name="create_login" value="0">
                        <input type="checkbox" name="create_login" value="1" x-model="f.createLogin">
                        <span class="as-switch" aria-hidden="true"></span>
                        <span><b>Create login now</b>Fill email + password with the owner — shown once after save.</span>
                    </label>
                    <div class="as-two" x-show="f.createLogin" x-cloak>
                        <label class="as-field">
                            <span class="as-label">Login email <em class="as-req">*</em></span>
                            <span class="as-prefix">
                                <i data-lucide="mail"></i>
                                <input class="f-input @error('login_email') is-invalid @enderror" name="login_email" type="email" x-model="f.loginEmail" maxlength="180" placeholder="owner@email.com" autocomplete="off" enterkeyhint="next">
                            </span>
                            @error('login_email')<span class="f-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="as-field">
                            <span class="as-label">Password <em class="as-req">*</em></span>
                            <span class="as-prefix">
                                <i data-lucide="lock"></i>
                                <input class="f-input @error('login_password') is-invalid @enderror" name="login_password" type="text" x-model="f.loginPassword" minlength="8" maxlength="64" placeholder="At least 8 characters" autocomplete="off" enterkeyhint="done">
                            </span>
                            @error('login_password')<span class="f-error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <p class="f-muted f-small" style="margin:10px 0 0" x-show="f.createLogin" x-cloak>They sign in at the partner portal with this email and password.</p>
                </div>
            </section>

            {{-- Interest & notes --}}
            <section class="f-panel as-sec">
                <div class="as-sec-head"><span class="f-ico tone-amber"><i data-lucide="flame"></i></span><div><h2>How interested are they?</h2><p>Tap again to clear</p></div></div>
                <div class="as-sec-body">
                    <input type="hidden" name="interest" :value="f.interest">
                    <div class="as-interest">
                        @foreach ($interests as $key => $label)
                            <button type="button" class="as-int as-int-{{ $key }}" :class="f.interest === '{{ $key }}' && 'on'" @click="f.interest = f.interest === '{{ $key }}' ? '' : '{{ $key }}'" :aria-pressed="f.interest === '{{ $key }}'">
                                <i data-lucide="{{ ['hot' => 'flame', 'warm' => 'thumbs-up', 'cold' => 'eye'][$key] }}"></i>
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <label class="as-field" style="margin-top:14px">
                        <span class="as-label">Notes for the office</span>
                        <textarea class="f-textarea @error('notes') is-invalid @enderror" name="notes" rows="2" maxlength="1000" placeholder="Shop size, brands they stock, best time to visit…">{{ old('notes') }}</textarea>
                        @error('notes')<span class="f-error">{{ $message }}</span>@enderror
                    </label>
                </div>
            </section>
            </div>
            </div>

            @if ($openVisit)
                <div class="f-warn" style="margin-top:0">You're still checked in at <b>{{ $openVisit->shop?->name }}</b>. Finish that visit to start one at the new shop.</div>
            @else
                <label class="as-visit">
                    <input type="checkbox" name="check_in" value="1" @checked(old('check_in', true))>
                    <span class="as-switch" aria-hidden="true"></span>
                    <span><b>I'm at this shop now</b>Start a visit right after saving — it counts towards today.</span>
                </label>
            @endif
        </div>

        {{-- Desktop preview --}}
        <aside class="as-side">
            <section class="f-panel as-preview">
                <div class="as-preview-top">
                    <span class="as-av" x-text="(f.name || 'N').trim().charAt(0).toUpperCase()"></span>
                    <div style="min-width:0">
                        <b x-text="f.name || 'New shop'"></b>
                        <span x-text="f.owner || 'Owner not added'"></span>
                    </div>
                </div>
                <ul class="as-facts">
                    <li :class="f.phone && 'ok'"><i data-lucide="phone"></i><span x-text="f.phone || 'No phone yet'"></span></li>
                    <li :class="(f.city || f.address) && 'ok'"><i data-lucide="map-pin"></i><span x-text="[f.address, f.city].filter(Boolean).join(', ') || 'No address yet'"></span></li>
                    <li :class="state === 'ok' && 'ok'"><i data-lucide="locate-fixed"></i><span x-text="state === 'ok' ? 'GPS location saved' : 'No GPS location'"></span></li>
                    <li :class="f.sells.length && 'ok'"><i data-lucide="package"></i><span x-text="f.sells.length ? f.sells.join(', ') : 'Products not picked'"></span></li>
                    <li :class="(!f.createLogin || (f.loginEmail && f.loginPassword)) && 'ok'"><i data-lucide="key-round"></i><span x-text="f.createLogin ? (f.loginEmail ? f.loginEmail : 'Login email needed') : 'Login skipped'"></span></li>
                </ul>
                <div class="as-meter">
                    <div class="as-meter-top"><span>Details added</span><b x-text="filled() + ' of 7'"></b></div>
                    <div class="as-meter-bar"><span :style="'width:' + Math.max(4, filled() / 7 * 100) + '%'"></span></div>
                </div>
                <button class="f-btn f-btn-primary f-btn-lg f-btn-block" type="submit" :disabled="saving">
                    <i data-lucide="check"></i> <span x-text="saving ? 'Saving…' : (f.createLogin ? 'Save shop + login' : 'Save shop')"></span>
                </button>
                <p class="as-foot"><i data-lucide="shield-check"></i> Shop is approved immediately. Owner login lets them use the partner portal.</p>
            </section>
        </aside>

        {{-- Phone save bar --}}
        <div class="f-cartbar as-bar">
            <div class="f-cartbar-text">
                <small x-text="filled() + ' of 7 · ' + (f.createLogin ? 'with login' : 'no login') + ' · ' + (state === 'ok' ? 'GPS' : 'no GPS')"></small>
                <strong x-text="f.name || 'New shop'"></strong>
            </div>
            <button class="f-btn f-btn-success" type="submit" :disabled="saving">
                <i data-lucide="check"></i> <span x-text="saving ? 'Saving…' : 'Save'"></span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
function addShop(initial) {
    return {
        f: initial,
        lat: null, lng: null, accuracy: null, state: 'busy', saving: false,
        filled() {
            return [
                this.f.name, this.f.owner, this.f.phone, this.f.city || this.f.address, this.f.sells.length, this.state === 'ok',
                !this.f.createLogin || (this.f.loginEmail && this.f.loginPassword),
            ].filter(Boolean).length;
        },
        locate() {
            if (!navigator.geolocation) { this.state = 'err'; return; }
            this.state = 'busy';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.accuracy = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : null;
                    this.state = 'ok';
                },
                () => { this.state = 'err'; },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 },
            );
        },
    };
}
</script>
@endpush
