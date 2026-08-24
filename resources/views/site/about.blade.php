@extends('site.layouts.app')
@section('title', 'About')
@section('content')
    <section class="site-page-hero">
        <div class="site-wrap">
            <p class="site-kicker">About</p>
            <h1 class="site-h2" style="max-width:16ch">Built for wholesale gadget distribution in Bangladesh.</h1>
            <p class="site-lead">Bynnas Trade is the private operating system behind China import, warehouse fulfilment, salesman routes, shop credit and commission — not a public marketplace.</p>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap site-split">
            <div>
                <p class="site-kicker">What we run</p>
                <h2 class="site-h2">One company stack. Three portals.</h2>
                <p class="site-lead">HQ controls users, shops, stock and audit. Approved shop owners order at their price group. Salesmen check in, collect orders, and chase targets on a mobile-friendly field app.</p>
            </div>
            <ul class="site-list site-panel">
                <li><strong>Super Admin & HQ</strong><span>RBAC, warehouses, shipments, audit queue, finance and analytics.</span></li>
                <li><strong>Shop portal</strong><span>Assigned prices, stock visibility, orders, invoices and returns.</span></li>
                <li><strong>Field force</strong><span>Visits, order collection, monthly targets and achievement.</span></li>
            </ul>
        </div>
    </section>

    <section class="site-section site-band">
        <div class="site-wrap site-split">
            <div>
                <p class="site-kicker">Operating promise</p>
                <h2 class="site-h2">No order ships without audit. No credit drifts unnoticed.</h2>
            </div>
            <p class="site-lead" style="margin:0">Shop and salesman orders wait for Super Admin approval before stock is reserved. Outstanding balances feed credit hold. Verified collections unlock commissions.</p>
        </div>
    </section>
@endsection
