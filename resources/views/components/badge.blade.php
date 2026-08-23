@props(['status' => null, 'status' => null])

@php
    $status = $status ?? $status;
    $map = [
        'Pending Approval' => 'badge-pending',
        'Pending' => 'badge-pending',
        'Under Review' => 'badge-review',
        'Approved' => 'badge-approved',
        'Active' => 'badge-active',
        'Operational' => 'badge-active',
        'Preferred' => 'badge-active',
        'Paid' => 'badge-paid',
        'Cleared' => 'badge-paid',
        'Completed' => 'badge-paid',
        'Delivered' => 'badge-delivered',
        'Received' => 'badge-delivered',
        'In Stock' => 'badge-active',
        'Processing' => 'badge-processing',
        'Shipped' => 'badge-shipped',
        'In Transit' => 'badge-transit',
        'Customs' => 'badge-customs',
        'On Hold' => 'badge-hold',
        'On Leave' => 'badge-hold',
        'Low Stock' => 'badge-low',
        'Out of Stock' => 'badge-out',
        'Unpaid' => 'badge-unpaid',
        'Partial' => 'badge-partial',
        'Shop Portal' => 'badge-processing',
        'Salesman' => 'badge-shipped',
    ];
    $class = $map[$status] ?? 'badge-hold';
@endphp

<span {{ $attributes->merge(['class' => "badge {$class}"]) }}>{{ $status }}</span>
