<?php

namespace App\Http\Controllers;

use App\Support\DemoData;

class UiController extends Controller
{
    public function dashboard()
    {
        return view('pages.dashboard', [
            'orders' => DemoData::orders(),
            'products' => DemoData::products(),
            'shipments' => DemoData::shipments(),
            'activities' => DemoData::activities(),
        ]);
    }

    public function page(string $page)
    {
        $views = [
            'shops' => 'pages.shops.index',
            'shops.create' => 'pages.shops.create',
            'salesmen' => 'pages.salesmen.index',
            'salesmen.create' => 'pages.salesmen.create',
            'orders' => 'pages.orders.index',
            'orders.create' => 'pages.orders.create',
            'returns' => 'pages.returns.index',
            'invoices' => 'pages.invoices.index',
            'payments' => 'pages.payments.index',
            'products' => 'pages.products.index',
            'products.create' => 'pages.products.create',
            'categories' => 'pages.categories.index',
            'inventory' => 'pages.inventory.index',
            'inventory.receive' => 'pages.inventory.receive',
            'warehouses' => 'pages.warehouses.index',
            'shipments' => 'pages.shipments.index',
            'shipments.create' => 'pages.shipments.create',
            'suppliers' => 'pages.suppliers.index',
            'purchases' => 'pages.purchases.index',
            'purchases.create' => 'pages.purchases.create',
            'reports' => 'pages.reports.index',
            'analytics' => 'pages.analytics.index',
            'users' => 'pages.users.index',
            'settings' => 'pages.settings.index',
        ];

        abort_unless(isset($views[$page]), 404);

        return view($views[$page], [
            'shops' => DemoData::shops(),
            'salesmen' => DemoData::salesmen(),
            'orders' => DemoData::orders(),
            'products' => DemoData::products(),
            'shipments' => DemoData::shipments(),
            'invoices' => DemoData::invoices(),
            'payments' => DemoData::payments(),
            'returns' => DemoData::returns(),
            'suppliers' => DemoData::suppliers(),
            'purchases' => DemoData::purchases(),
            'warehouses' => DemoData::warehouses(),
            'categories' => DemoData::categories(),
            'users' => DemoData::users(),
        ]);
    }

    public function show(string $resource, string $id)
    {
        $map = [
            'orders' => 'pages.orders.show',
            'shops' => 'pages.shops.show',
            'salesmen' => 'pages.salesmen.show',
            'shipments' => 'pages.shipments.show',
            'invoices' => 'pages.invoices.show',
        ];

        abort_unless(isset($map[$resource]), 404);

        return view($map[$resource], [
            'id' => $id,
            'shops' => DemoData::shops(),
            'salesmen' => DemoData::salesmen(),
            'orders' => DemoData::orders(),
            'products' => DemoData::products(),
            'shipments' => DemoData::shipments(),
            'invoices' => DemoData::invoices(),
        ]);
    }
}
