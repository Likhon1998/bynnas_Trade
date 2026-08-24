@extends('site.layouts.app')
@section('title', 'Contact')
@section('content')
    <section class="site-page-hero">
        <div class="site-wrap">
            <p class="site-kicker">Contact</p>
            <h1 class="site-h2">Talk to the Bynnas Trade team.</h1>
            <p class="site-lead">Partnerships, wholesale enquiries and general questions — we route messages to HQ.</p>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap site-split">
            <div class="site-panel">
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
                    <label>Subject<input name="subject" value="{{ old('subject') }}" placeholder="Wholesale partnership / general"></label>
                    <label>Message<textarea name="message" rows="5" required>{{ old('message') }}</textarea></label>
                    <button class="site-cta" type="submit">Send message</button>
                </form>
            </div>
            <div>
                <p class="site-kicker">HQ</p>
                <h2 class="site-h2">Dhaka operations</h2>
                <p class="site-lead">Tejgaon Industrial Area, Dhaka · Wholesale gadget distribution desk</p>
                <ul class="site-list">
                    <li><strong>Email</strong><span>hello@bynnastrade.com</span></li>
                    <li><strong>Phone</strong><span>+880 1700-000000</span></li>
                    <li><strong>Hours</strong><span>Sat–Thu, 10:00–18:00</span></li>
                </ul>
            </div>
        </div>
    </section>
@endsection
