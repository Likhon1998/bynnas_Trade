@props(['status' => null])

@php
    $map = [
        'Pending Approval' => 'badge-pending',
        'Pending Super Admin Audit' => 'badge-pending',
        'Pending' => 'badge-pending',
        'Under Review' => 'badge-review',
        'Approved' => 'badge-approved',
        'Approved · Stock Reserved' => 'badge-approved',
        'Approved · Reserved' => 'badge-approved',
        'Active' => 'badge-active',
        'Operational' => 'badge-active',
        'Preferred' => 'badge-active',
        'Paid' => 'badge-paid',
        'Cleared' => 'badge-paid',
        'Completed' => 'badge-paid',
        'Awaiting pick' => 'badge-pending',
        'Picking' => 'badge-processing',
        'Picked' => 'badge-processing',
        'Packed' => 'badge-shipped',
        'Shipped' => 'badge-shipped',
        'Dispatched' => 'badge-transit',
        'In Transit' => 'badge-transit',
        'Delivered' => 'badge-delivered',
        'Received' => 'badge-delivered',
        'In Stock' => 'badge-active',
        'Processing' => 'badge-processing',
        'Customs' => 'badge-customs',
        'On Hold' => 'badge-hold',
        'On Leave' => 'badge-hold',
        'Low Stock' => 'badge-low',
        'Out of Stock' => 'badge-out',
        'Rejected' => 'badge-out',
        'Cancelled' => 'badge-hold',
        'Unpaid' => 'badge-unpaid',
        'Partial' => 'badge-partial',
        'Shop Portal' => 'badge-processing',
        'Salesman' => 'badge-shipped',
    ];
    $class = $map[$status] ?? 'badge-hold';
@endphp

<span {{ $attributes->merge(['class' => "badge {$class}"]) }}>{{ $status }}</span>
