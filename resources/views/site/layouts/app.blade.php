<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') · Bynnas Trade</title>
    <meta name="description" content="@yield('meta', 'Bynnas Trade — B2B wholesale distribution from China import to warehouse fulfilment and shop delivery in Bangladesh.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @include('partials.favicon')
    @stack('head')
</head>
<body class="site-body" x-data="{ navOpen: false }">
    <header class="site-nav">
        <a class="site-brand" href="{{ route('site.home') }}">
            <x-brand-logo :size="56" show-wordmark />
        </a>

        <button class="site-nav-toggle" type="button" @click="navOpen = !navOpen" aria-label="Menu">Menu</button>

        <nav class="site-nav-links" :class="navOpen && 'open'">
            <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'is-active' : '' }}">Home</a>
            <a href="{{ route('site.about') }}" class="{{ request()->routeIs('site.about') ? 'is-active' : '' }}">About</a>
            <a href="{{ route('site.contact') }}" class="{{ request()->routeIs('site.contact') ? 'is-active' : '' }}">Contact</a>
            <a href="{{ route('site.partner') }}" class="{{ request()->routeIs('site.partner') ? 'is-active' : '' }}">Become a Partner</a>
            <a class="site-cta" href="{{ route('portal.login') }}" style="padding:10px 16px">Shop login</a>
        </nav>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="site-wrap site-footer-grid">
            <div>
                <strong class="site-footer-brand">Bynnas Trade</strong>
                <div style="margin-top:8px">B2B wholesale · Bangladesh</div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap">
                <a href="{{ route('site.about') }}">About</a>
                <a href="{{ route('site.contact') }}">Contact</a>
                <a href="{{ route('site.partner') }}">Partner</a>
                <a href="{{ route('login') }}">Admin</a>
                <a href="{{ route('field.login') }}">Field</a>
            </div>
        </div>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
