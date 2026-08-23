@extends('layouts.app')
@section('title', 'Reports')
@section('content')
    <x-page-header title="Reports" subtitle="Export-ready operational packs">
        <x-slot:description>Management packs cover sales, profit, inventory, shipments, credit and salesman performance. Generation will bind to live data later.</x-slot:description>
    </x-page-header>
    <div class="grid-4">
        @foreach ([
            ['Sales register', 'Billed orders, shops and salesmen for the selected period.'],
            ['Outstanding credit', 'Shop-wise receivables, ageing and credit-limit utilisation.'],
            ['Inventory movement', 'Reservations, picks, receipts and warehouse balances.'],
            ['Shipment costing', 'Freight, customs and landed cost by inbound container.'],
            ['Salesman commission', 'Approved collections against configurable commission rules.'],
            ['Returns & warranty', 'Defects, replacements and credit notes awaiting audit.'],
            ['Procurement', 'Open POs, supplier exposure and shipment linkage.'],
            ['Target vs achievement', 'Territory targets, visits and conversion for the field force.'],
        ] as $report)
            <div class="card" style="padding:18px">
                <div class="section-title">{{ $report[0] }}</div>
                <p class="muted">{{ $report[1] }}</p>
                <button class="btn btn-ghost" type="button">Generate preview</button>
            </div>
        @endforeach
    </div>
@endsection
