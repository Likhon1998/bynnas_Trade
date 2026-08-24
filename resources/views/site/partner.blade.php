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
                        <select name="business_type">
                            <option value="retailer" @selected(old('business_type', 'retailer') === 'retailer')>Retailer</option>
                            <option value="distributor" @selected(old('business_type') === 'distributor')>Distributor</option>
                            <option value="other" @selected(old('business_type') === 'other')>Other</option>
                        </select>
                    </label>
                    <label>About your shop / volumes<textarea name="message" rows="4" placeholder="Categories you sell, monthly purchase estimate, existing locations">{{ old('message') }}</textarea></label>
                    <button class="site-cta" type="submit">Submit application</button>
                </form>
            </div>
        </div>
    </section>
@endsection
