@props([
    'size' => 36,
    'showWordmark' => false,
    'wordmark' => 'Bynnas Trade',
])

<span {{ $attributes->class('brand-logo') }}>
    <img src="{{ asset('images/logo.png') }}" alt="Bynnas" width="{{ $size }}" height="{{ $size }}">
    @if ($showWordmark)
        <span class="brand-wordmark">{{ $wordmark }}</span>
    @endif
</span>
