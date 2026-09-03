@props([
    'product',
    'size' => 48,
])

@php
    $url = $product->imageUrl();
    $label = $product->name ?? 'Product';
@endphp

@if ($url)
    <img
        src="{{ $url }}"
        alt="{{ $label }}"
        width="{{ $size }}"
        height="{{ $size }}"
        class="product-thumb"
        style="width:{{ $size }}px;height:{{ $size }}px"
        loading="lazy"
    >
@else
    <span class="product-thumb product-thumb-empty" style="width:{{ $size }}px;height:{{ $size }}px" aria-hidden="true">
        {{ strtoupper(substr($label, 0, 1)) }}
    </span>
@endif
