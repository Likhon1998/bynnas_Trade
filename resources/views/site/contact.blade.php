@extends('site.layouts.app')
@section('title', 'Contact')
@section('content')
    <section class="page-hero">
        <div class="site-wrap">
            <span class="eyebrow">Contact</span>
            <h1>Get in touch with our team.</h1>
            <p class="lead">Questions about partnerships, wholesale orders or anything else — send us a message and we'll respond within one business day.</p>
        </div>
    </section>

    <section class="section">
        <div class="site-wrap split split-form">
            <div class="card">
                <h2 class="card-title">Send a message</h2>
                @if (session('success'))
                    <div class="site-alert site-alert-ok">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="site-alert site-alert-err">{{ $errors->first() }}</div>
                @endif

                <form class="site-form" method="post" action="{{ route('site.contact.store') }}">
                    @csrf
                    <div class="site-form-row">
                        <label>Name<input name="name" value="{{ old('name') }}" required></label>
                        <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
                    </div>
                    <label>Subject<input name="subject" value="{{ old('subject') }}" placeholder="Wholesale partnership, order enquiry…"></label>
                    <label>Message<textarea name="message" rows="5" required>{{ old('message') }}</textarea></label>
                    <div><button class="btn btn-primary btn-lg" type="submit">Send message</button></div>
                </form>
            </div>

            <div class="contact-side">
                <div class="card">
                    <h2 class="card-title">Head office</h2>
                    <p class="muted">Tejgaon Industrial Area, Dhaka</p>
                    <div class="contact-list">
                        <div class="contact-row">
                            <span class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/><path d="M22 6l-10 7L2 6"/></svg></span>
                            <div><span class="contact-label">Email</span>hello@bynnastrade.com</div>
                        </div>
                        <div class="contact-row">
                            <span class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/></svg></span>
                            <div><span class="contact-label">Phone</span>+880 1700-000000</div>
                        </div>
                        <div class="contact-row">
                            <span class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                            <div><span class="contact-label">Business hours</span>Saturday – Thursday, 10:00 – 18:00</div>
                        </div>
                    </div>
                </div>
                <div class="card card-soft">
                    <h3>Want to become a partner?</h3>
                    <p class="muted">Apply online and our team will review your shop.</p>
                    <a class="btn btn-outline" href="{{ route('site.partner') }}">Become a partner</a>
                </div>
            </div>
        </div>
    </section>
@endsection
