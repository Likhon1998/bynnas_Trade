@php
    $g = $site->section('general');
    $socials = array_filter(['Facebook' => $g['facebook_url'], 'LinkedIn' => $g['linkedin_url'], 'YouTube' => $g['youtube_url']]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') · {{ $site->siteName() }}</title>
    <meta name="description" content="@yield('meta', $g['meta_description'])">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
    @include('partials.favicon')
    @stack('head')
</head>
<body class="site-body" x-data="{ navOpen: false }">
    <header class="site-header">
        <div class="site-wrap site-header-inner">
            <a class="site-brand" href="{{ route('site.home') }}" aria-label="{{ $site->siteName() }} home">
                <x-brand-logo :size="36" show-wordmark />
            </a>

            <nav class="site-nav" :class="navOpen && 'is-open'" aria-label="Main">
                <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'is-active' : '' }}">Home</a>
                <a href="{{ route('site.about') }}" class="{{ request()->routeIs('site.about') ? 'is-active' : '' }}">About</a>
                <a href="{{ route('site.contact') }}" class="{{ request()->routeIs('site.contact') ? 'is-active' : '' }}">Contact</a>
                <div class="site-nav-actions">
                    @if ($g['show_shop_login'])
                        <a class="btn btn-outline" href="{{ route('portal.login') }}">Shop login</a>
                    @endif
                    @if ($g['show_partner_button'])
                        <a class="btn btn-primary" href="{{ route('site.partner') }}" @if (request()->routeIs('site.partner')) aria-current="page" @endif>Become a partner</a>
                    @endif
                </div>
            </nav>

            <button class="site-nav-toggle" type="button" @click="navOpen = !navOpen" :aria-expanded="navOpen" aria-label="Toggle menu">
                <svg x-show="!navOpen" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="navOpen" x-cloak width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="site-wrap">
            <div class="site-footer-grid">
                <div class="site-footer-about">
                    <x-brand-logo :size="32" show-wordmark />
                    @if ($g['footer_about'])<p>{{ $g['footer_about'] }}</p>@endif
                    @if ($socials)
                        <div class="site-socials">
                            @foreach ($socials as $label => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener">{{ $label }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <h4>Company</h4>
                    <a href="{{ route('site.about') }}">About us</a>
                    <a href="{{ route('site.contact') }}">Contact</a>
                    @if ($g['show_partner_button'])
                        <a href="{{ route('site.partner') }}">Become a partner</a>
                    @endif
                </div>
                <div>
                    <h4>Sign in</h4>
                    @if ($g['show_shop_login'])<a href="{{ route('portal.login') }}">Shop portal</a>@endif
                    @if ($g['show_field_login'])<a href="{{ route('field.login') }}">Field team</a>@endif
                    @if ($g['show_admin_login'])<a href="{{ route('login') }}">Admin</a>@endif
                </div>
                <div>
                    <h4>Contact</h4>
                    @if ($g['email'])<a href="mailto:{{ $g['email'] }}">{{ $g['email'] }}</a>@endif
                    @if ($g['phone'])<a href="{{ $site->telLink($g['phone']) }}">{{ $g['phone'] }}</a>@endif
                    @if ($g['hours'])<span>{{ $g['hours'] }}</span>@endif
                </div>
            </div>
            <div class="site-footer-bottom">
                <span>© {{ date('Y') }} {{ $site->siteName() }}. All rights reserved.</span>
                @if ($g['address'])<span>{{ $g['address'] }}</span>@endif
            </div>
        </div>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
