@extends('site.layouts.app')
@section('title', 'Contact')
@php
    $c = $site->section('contact');
    $g = $site->section('general');
@endphp
@section('content')
    <section class="page-hero">
        <div class="site-wrap">
            @if ($c['hero_eyebrow'])<span class="eyebrow">{{ $c['hero_eyebrow'] }}</span>@endif
            <h1>{{ $c['hero_title'] }}</h1>
            @if ($c['hero_lead'])<p class="lead">{{ $c['hero_lead'] }}</p>@endif
        </div>
    </section>

    <section class="section">
        <div class="site-wrap split split-form">
            <div class="card">
                <h2 class="card-title">{{ $c['form_title'] ?: 'Send a message' }}</h2>
                @if (session('success'))
                    <div class="site-alert site-alert-ok">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="site-alert site-alert-err">{{ $errors->first() }}</div>
                @endif

                <form class="site-form" method="post" action="{{ route('site.contact.store') }}">
                    @csrf
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;opacity:0">

                    <div class="site-form-row">
                        <label>Name<input name="name" value="{{ old('name') }}" required></label>
                        <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
                    </div>
                    <label>Subject<input name="subject" value="{{ old('subject') }}" placeholder="{{ $c['subject_placeholder'] }}"></label>
                    <label>Message<textarea name="message" rows="5" required>{{ old('message') }}</textarea></label>
                    <div><button class="btn btn-primary btn-lg" type="submit">Send message</button></div>
                </form>
            </div>

            <div class="contact-side">
                <div class="card">
                    <h2 class="card-title">{{ $c['office_title'] ?: 'Head office' }}</h2>
                    @if ($g['address'])
                        <p class="muted">
                            {{ $g['address'] }}
                            @if ($g['map_url'])· <a class="link" href="{{ $g['map_url'] }}" target="_blank" rel="noopener">View map</a>@endif
                        </p>
                    @endif
                    <div class="contact-list">
                        @if ($g['email'])
                            <div class="contact-row">
                                <span class="contact-icon">@include('site.partials.icon', ['name' => 'mail'])</span>
                                <div><span class="contact-label">Email</span><a href="mailto:{{ $g['email'] }}">{{ $g['email'] }}</a></div>
                            </div>
                        @endif
                        @if ($g['phone'])
                            <div class="contact-row">
                                <span class="contact-icon">@include('site.partials.icon', ['name' => 'phone'])</span>
                                <div><span class="contact-label">Phone</span><a href="{{ $site->telLink($g['phone']) }}">{{ $g['phone'] }}</a></div>
                            </div>
                        @endif
                        @if ($g['whatsapp'])
                            <div class="contact-row">
                                <span class="contact-icon">@include('site.partials.icon', ['name' => 'chat'])</span>
                                <div><span class="contact-label">WhatsApp</span><a href="{{ $site->whatsappLink($g['whatsapp']) }}" target="_blank" rel="noopener">{{ $g['whatsapp'] }}</a></div>
                            </div>
                        @endif
                        @if ($g['hours'])
                            <div class="contact-row">
                                <span class="contact-icon">@include('site.partials.icon', ['name' => 'clock'])</span>
                                <div><span class="contact-label">Business hours</span>{{ $g['hours'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>
                @if ($g['show_partner_button'] && $c['partner_card_title'])
                    <div class="card card-soft">
                        <h3>{{ $c['partner_card_title'] }}</h3>
                        @if ($c['partner_card_text'])<p class="muted">{{ $c['partner_card_text'] }}</p>@endif
                        <a class="btn btn-outline" href="{{ route('site.partner') }}">Become a partner</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
