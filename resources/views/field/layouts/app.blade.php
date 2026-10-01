<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#16141F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Field') · Bynnas Trade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/field.css') }}?v={{ filemtime(public_path('css/field.css')) }}">
    @include('partials.favicon')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@0.469.0"></script>
    <script>try { if (localStorage.getItem('field.sidebar') === 'mini') document.documentElement.classList.add('f-side-mini'); } catch (e) {}</script>
</head>
@php
    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user?->name))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $onVisitPage = request()->routeIs('field.visit.show') && optional(request()->route('visit'))->id === $fieldOpenVisit?->id;
@endphp
<body class="f-app">
    <div class="f-shell @hasSection('has-bar') has-bar @endif">
        <header class="f-top">
            @hasSection('back')
                <a class="f-back" href="@yield('back')" aria-label="Back"><i data-lucide="arrow-left"></i></a>
            @else
                <div class="f-avatar" aria-hidden="true">{{ strtoupper($initials ?: 'S') }}</div>
            @endif
            <div class="f-top-text">
                <h1 class="f-top-title">@yield('heading', 'Field')</h1>
                <div class="f-top-sub">@yield('subheading', trim(($user?->name ?? '').($fieldProfile?->employee_code ? ' · '.$fieldProfile->employee_code : '')))</div>
            </div>
            <div class="f-top-actions">@yield('actions')</div>
            <form action="{{ route('field.logout') }}" method="post" onsubmit="return confirm('Sign out of the field app?')">
                @csrf
                <button type="submit" class="f-icon-btn" aria-label="Sign out" title="Sign out"><i data-lucide="log-out"></i></button>
            </form>
        </header>

        <main class="f-body">
            @if (session('success'))
                <div class="f-flash ok" role="status"><i data-lucide="check-circle-2"></i><span>{{ session('success') }}</span></div>
            @endif
            @if (session('error'))
                <div class="f-flash err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>
            @endif
            @if ($errors->any())
                <div class="f-flash err" role="alert"><i data-lucide="alert-circle"></i><span>{{ $errors->first() }}</span></div>
            @endif

            @if ($fieldOpenVisit && ! $onVisitPage)
                <a class="f-resume" href="{{ route('field.visit.show', $fieldOpenVisit) }}">
                    <span class="dot" aria-hidden="true"></span>
                    <span class="f-resume-text">
                        <strong>Visit in progress · {{ $fieldOpenVisit->shop?->name }}</strong>
                        <span>Checked in {{ $fieldOpenVisit->checked_in_at?->diffForHumans() }} — tap to take the order</span>
                    </span>
                    <span class="f-btn f-btn-warn f-btn-sm">Continue</span>
                </a>
            @endif

            @yield('content')
        </main>

        @yield('bar')

        <nav class="f-nav" aria-label="Main">
            <a class="f-nav-brand" href="{{ route('field.dashboard') }}" title="Bynnas Trade">
                <img src="{{ asset('images/logo.png') }}" alt="">
                <span><b>Bynnas Trade</b><small>Field sales app</small></span>
            </a>
            <button type="button" class="f-side-toggle" id="f-side-toggle" aria-label="Collapse sidebar" title="Collapse sidebar" aria-expanded="true"><i data-lucide="chevron-left"></i></button>
            <span class="f-nav-label">Menu</span>
            <a href="{{ route('field.dashboard') }}" title="Home" class="tone-violet {{ request()->routeIs('field.dashboard') ? 'active' : '' }}"><span class="f-nav-ico"><i data-lucide="layout-dashboard"></i></span><span class="f-nav-text">Home</span></a>
            <a href="{{ route('field.shops') }}" title="Shops" class="tone-blue {{ request()->routeIs('field.shops', 'field.shops.*', 'field.visit.*') ? 'active' : '' }}">
                <span class="f-nav-ico"><i data-lucide="store"></i></span><span class="f-nav-text">Shops</span>
                @if ($fieldOpenVisit)<span class="badge-dot" aria-label="Visit in progress">1</span>@endif
            </a>
            <a href="{{ route('field.orders') }}" title="Orders" class="tone-amber {{ request()->routeIs('field.orders*') ? 'active' : '' }}"><span class="f-nav-ico"><i data-lucide="receipt-text"></i></span><span class="f-nav-text">Orders</span></a>
            <a href="{{ route('field.partners') }}" title="Partners" class="tone-teal {{ request()->routeIs('field.partners*') ? 'active' : '' }}"><span class="f-nav-ico"><i data-lucide="handshake"></i></span><span class="f-nav-text">Partners</span></a>
            <a href="{{ route('field.earnings') }}" title="Earnings" class="tone-green {{ request()->routeIs('field.earnings') ? 'active' : '' }}"><span class="f-nav-ico"><i data-lucide="wallet"></i></span><span class="f-nav-text">Earnings</span></a>
            <div class="f-nav-user">
                <div class="f-avatar" aria-hidden="true">{{ strtoupper($initials ?: 'S') }}</div>
                <div class="f-nav-user-text">
                    <b>{{ $user?->name }}</b>
                    <small>{{ $fieldProfile?->employee_code ?: 'Field salesman' }}</small>
                </div>
                <form action="{{ route('field.logout') }}" method="post" onsubmit="return confirm('Sign out of the field app?')">
                    @csrf
                    <button type="submit" class="f-icon-btn" aria-label="Sign out" title="Sign out"><i data-lucide="log-out"></i></button>
                </form>
            </div>
        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => window.lucide && lucide.createIcons());

        (() => {
            const btn = document.getElementById('f-side-toggle');
            const sync = () => {
                const mini = document.documentElement.classList.contains('f-side-mini');
                const label = mini ? 'Expand sidebar' : 'Collapse sidebar';
                btn.setAttribute('aria-expanded', String(!mini));
                btn.setAttribute('aria-label', label);
                btn.title = label;
            };
            sync();
            btn.addEventListener('click', () => {
                const mini = document.documentElement.classList.toggle('f-side-mini');
                try { localStorage.setItem('field.sidebar', mini ? 'mini' : 'full'); } catch (e) {}
                sync();
            });
        })();

        // Attach GPS coordinates to check-in forms when the browser allows it; never block the check-in.
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form.matches('[data-checkin]') || form.dataset.located) return;
            e.preventDefault();
            const btn = form.querySelector('button[type=submit]');
            if (btn) { btn.disabled = true; btn.dataset.label = btn.innerHTML; btn.innerHTML = 'Checking in…'; }
            const go = () => { form.dataset.located = '1'; form.submit(); };
            if (!navigator.geolocation) return go();
            const timer = setTimeout(go, 5000);
            navigator.geolocation.getCurrentPosition((pos) => {
                clearTimeout(timer);
                form.querySelector('[name=latitude]').value = pos.coords.latitude.toFixed(7);
                form.querySelector('[name=longitude]').value = pos.coords.longitude.toFixed(7);
                go();
            }, () => { clearTimeout(timer); go(); }, { enableHighAccuracy: true, timeout: 4500, maximumAge: 60000 });
        });
    </script>
    @stack('scripts')
</body>
</html>
