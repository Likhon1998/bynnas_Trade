@extends('site.layouts.app')
@section('title', 'Wholesale gadget distribution')
@section('content')
    <section class="site-hero">
        <div class="site-hero-inner">
            <h1 class="site-hero-brand">Bynnas Trade</h1>
            <p class="site-hero-line">Private B2B wholesale for gadget retailers — China sourcing, Dhaka warehousing, and shop delivery on one controlled platform.</p>
            <div class="site-hero-actions">
                <a class="site-cta" href="{{ route('site.partner') }}">Become a partner</a>
                <a class="site-cta site-cta-ghost" href="{{ route('site.about') }}">How it works</a>
            </div>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap site-split">
            <div>
                <p class="site-kicker">The cycle</p>
                <h2 class="site-h2">Import to invoice, without the grey market chaos.</h2>
                <p class="site-lead">Every SKU stays tied to shipment, warehouse stock, salesman visit, Super Admin audit, delivery and collection — so credit and commission stay honest.</p>
            </div>
            <ul class="site-list site-panel">
                <li><strong>China inbound</strong><span>Purchase orders, containers, freight and landed cost on one record.</span></li>
                <li><strong>Warehouse control</strong><span>Reserve on approval, pick / pack / dispatch with live balances.</span></li>
                <li><strong>Shop & field</strong><span>Partner portal pricing plus salesman visits and order collection.</span></li>
                <li><strong>Finance close</strong><span>Invoices, verified payments, credit hold, returns and commissions.</span></li>
            </ul>
        </div>
    </section>

    <section class="site-section site-band">
        <div class="site-wrap" style="text-align:center">
            <p class="site-kicker">For retailers</p>
            <h2 class="site-h2" style="max-width:18ch;margin-left:auto;margin-right:auto">Ready to stock with assigned wholesale pricing?</h2>
            <p class="site-lead" style="margin-left:auto;margin-right:auto">Apply once. After Super Admin approval you get secure portal credentials for catalogue, orders, invoices and credit.</p>
            <a class="site-cta" href="{{ route('site.partner') }}">Apply as a shop partner</a>
        </div>
    </section>
@endsection
