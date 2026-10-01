@extends('site.layouts.app')
@section('title', 'About')
@section('content')
    <section class="page-hero">
        <div class="site-wrap">
            <span class="eyebrow">About us</span>
            <h1>Wholesale distribution, built for Bangladesh.</h1>
            <p class="lead">Bynnas Trade supplies retail shops with imported products — managing sourcing, warehousing, orders, delivery and payments in one place. We work with approved partners, not as a public marketplace.</p>
        </div>
    </section>

    <section class="section">
        <div class="site-wrap split">
            <div>
                <span class="eyebrow">What we do</span>
                <h2>One team, three ways to work with us.</h2>
                <p class="lead">Our head office manages stock, partners and quality control. Shop owners order at their own prices. Our field team supports shops directly.</p>
            </div>
            <div class="info-list">
                <div class="info-item">
                    <h3>Head office</h3>
                    <p>Warehouses, shipments, order review, finance and reporting.</p>
                </div>
                <div class="info-item">
                    <h3>Shop portal</h3>
                    <p>Partner prices, available stock, orders, invoices and returns.</p>
                </div>
                <div class="info-item">
                    <h3>Field team</h3>
                    <p>Shop visits, order collection and on-the-ground support.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="site-wrap">
            <div class="section-head">
                <span class="eyebrow">Our commitments</span>
                <h2>How we work with every partner.</h2>
            </div>
            <div class="features features-3">
                <div class="feature">
                    <h3>Every order is reviewed</h3>
                    <p>Orders are checked by our team before stock is reserved, so what you order is what you receive.</p>
                </div>
                <div class="feature">
                    <h3>Transparent accounts</h3>
                    <p>Invoices, payments and outstanding balances are always visible in your shop portal.</p>
                </div>
                <div class="feature">
                    <h3>Fair, consistent pricing</h3>
                    <p>Your price list is agreed upfront and applied automatically to every order.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="site-wrap">
            <div class="cta-band">
                <div>
                    <h2>Interested in becoming a partner?</h2>
                    <p>Tell us about your shop and our team will get back to you.</p>
                </div>
                <div class="cta-band-actions">
                    <a class="btn btn-white btn-lg" href="{{ route('site.partner') }}">Become a partner</a>
                </div>
            </div>
        </div>
    </section>
@endsection
