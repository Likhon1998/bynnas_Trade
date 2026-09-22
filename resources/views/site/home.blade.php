@extends('site.layouts.app')
@section('title', 'Home')
@section('meta', 'Bynnas Trade — B2B wholesale distribution for retail partners in Bangladesh.')
@section('content')
    <section class="site-hero">
        <div class="site-hero-inner">
            <h1 class="site-hero-brand">Bynnas Trade</h1>
            <p class="site-hero-line">B2B wholesale supply for retail shops. We import, store, and deliver products to approved partners.</p>
            <div class="site-hero-actions">
                <a class="site-cta" href="{{ route('site.partner') }}">Become a partner</a>
                <a class="site-cta site-cta-ghost" href="{{ route('site.about') }}">About us</a>
            </div>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap site-split">
            <div>
                <p class="site-kicker">What we do</p>
                <h2 class="site-h2">Simple wholesale operations.</h2>
                <p class="site-lead">We handle import, warehouse stock, partner orders, delivery, and payments in one system.</p>
            </div>
            <ul class="site-list site-panel">
                <li><strong>Import</strong><span>Products sourced and brought into Bangladesh.</span></li>
                <li><strong>Warehouse</strong><span>Stock checked, packed, and prepared for delivery.</span></li>
                <li><strong>Orders</strong><span>Approved shops place orders at their wholesale price.</span></li>
                <li><strong>Payment</strong><span>Invoices, collections, and account balance in one place.</span></li>
            </ul>
        </div>
    </section>

    <section class="site-section site-band">
        <div class="site-wrap" style="text-align:center">
            <p class="site-kicker">For shops</p>
            <h2 class="site-h2" style="max-width:20ch;margin-left:auto;margin-right:auto">Want to buy wholesale from us?</h2>
            <p class="site-lead" style="margin-left:auto;margin-right:auto">Apply as a partner. After approval, you can log in to view products, place orders, and check invoices.</p>
            <a class="site-cta" href="{{ route('site.partner') }}">Apply now</a>
        </div>
    </section>
@endsection
