@extends('site.layouts.app')
@section('title', 'Home')
@section('meta', 'Bynnas Trade — B2B wholesale distribution for retail partners in Bangladesh.')
@section('content')
    <section class="hero">
        <div class="site-wrap hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">B2B wholesale distribution · Bangladesh</span>
                <h1>Reliable wholesale supply for your retail shop.</h1>
                <p class="lead">We import, store and deliver products to approved retail partners — with partner pricing, clear invoices and a dedicated sales team.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="{{ route('site.partner') }}">Become a partner</a>
                    <a class="btn btn-outline btn-lg" href="{{ route('site.about') }}">Learn more</a>
                </div>
                <ul class="hero-points">
                    <li>Partner pricing</li>
                    <li>Reviewed orders</li>
                    <li>Online invoices</li>
                </ul>
            </div>
            <div class="hero-media">
                <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80" alt="Bynnas Trade warehouse" loading="eager">
                <div class="hero-card">
                    <div class="hero-card-title">Order status</div>
                    <div class="hero-card-row"><span class="dot dot-done"></span>Order approved</div>
                    <div class="hero-card-row"><span class="dot dot-done"></span>Packed at warehouse</div>
                    <div class="hero-card-row"><span class="dot dot-active"></span>Out for delivery</div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="site-wrap">
            <div class="section-head">
                <span class="eyebrow">How it works</span>
                <h2>From import to your shop, in four steps.</h2>
                <p class="lead">One team handles the full supply chain, so you can focus on selling.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <span class="step-num">1</span>
                    <h3>Import</h3>
                    <p>Products are sourced and brought into Bangladesh by our team.</p>
                </div>
                <div class="step">
                    <span class="step-num">2</span>
                    <h3>Warehouse</h3>
                    <p>Stock is checked, stored and prepared at our Dhaka warehouse.</p>
                </div>
                <div class="step">
                    <span class="step-num">3</span>
                    <h3>Order</h3>
                    <p>Approved shops order online or through their sales representative.</p>
                </div>
                <div class="step">
                    <span class="step-num">4</span>
                    <h3>Deliver &amp; invoice</h3>
                    <p>Orders are delivered to your shop with a clear invoice and payment record.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="site-wrap">
            <div class="section-head">
                <span class="eyebrow">Why partner with us</span>
                <h2>Built for shops that buy regularly.</h2>
            </div>
            <div class="features">
                <div class="feature">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L3 13V3h10l7.6 7.6a2 2 0 010 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
                    <h3>Partner pricing</h3>
                    <p>Each shop gets a price list based on its partnership level and volume.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg></span>
                    <h3>Verified stock</h3>
                    <p>See what is available before ordering — no surprises after you place an order.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></span>
                    <h3>Reviewed orders</h3>
                    <p>Every order is checked by our team before stock is reserved and dispatched.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                    <h3>Clear invoices</h3>
                    <p>Download invoices and track payments and balances from your shop portal.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <h3>Dedicated sales rep</h3>
                    <p>A field representative visits your shop and helps with orders and collections.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></span>
                    <h3>Doorstep delivery</h3>
                    <p>Orders are packed at our warehouse and delivered directly to your shop.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="site-wrap">
            <div class="cta-band">
                <div>
                    <h2>Ready to buy wholesale from Bynnas Trade?</h2>
                    <p>Apply as a partner. Once approved, you can log in to view products, place orders and manage invoices.</p>
                </div>
                <div class="cta-band-actions">
                    <a class="btn btn-white btn-lg" href="{{ route('site.partner') }}">Become a partner</a>
                    <a class="btn btn-ghost-light btn-lg" href="{{ route('site.contact') }}">Contact sales</a>
                </div>
            </div>
        </div>
    </section>
@endsection
