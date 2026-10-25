@php
    $version = \App\Support\Favicon::version();
@endphp
<link rel="icon" href="{{ route('favicon.ico', ['v' => $version]) }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ route('favicon.png', ['size' => 32, 'v' => $version]) }}">
<link rel="icon" type="image/png" sizes="48x48" href="{{ route('favicon.png', ['size' => 48, 'v' => $version]) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ route('favicon.png', ['size' => 180, 'v' => $version]) }}">
