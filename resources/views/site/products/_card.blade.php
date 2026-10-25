@props(['product'])
@php
    $url = $product->publicOrderUrl();
    $price = $product->publicPrice();
@endphp
<article class="site-product">
    <a class="site-product-media" href="{{ route('site.products.show', $product) }}">
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="site-product-placeholder">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
        @endif
    </a>
    <div class="site-product-body">
        <div class="site-product-meta">
            @if ($product->category)<span>{{ $product->category->name }}</span>@endif
            @if ($product->brand)<span>{{ $product->brand->name }}</span>@endif
        </div>
        <h3><a href="{{ route('site.products.show', $product) }}">{{ $product->name }}</a></h3>
        <div class="site-product-row">
            @if ($price)
                <div class="site-product-price">{{ \App\Support\DemoData::taka($price) }}</div>
            @endif
            <a class="btn btn-primary" href="{{ $url }}" target="_blank" rel="noopener noreferrer">Order</a>
        </div>
    </div>
</article>
