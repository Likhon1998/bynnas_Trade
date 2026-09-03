<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>@yield('title', 'Field') · Bynnas Trade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @include('partials.favicon')
    <style>
        :root { --field-nav: 64px; }
        body { background: #f3f4f6; }
        .field-shell { min-height: 100vh; max-width: 480px; margin: 0 auto; padding-bottom: calc(var(--field-nav) + 16px); background: #f8fafc; }
        .field-top { position: sticky; top: 0; z-index: 20; background: #120f1c; color: #fff; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
        .field-top h1 { font-size: 16px; margin: 0; font-weight: 700; }
        .field-top .muted { color: #94a3b8; font-size: 12px; }
        .field-body { padding: 14px 14px 0; }
        .field-nav { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; height: var(--field-nav); background: #fff; border-top: 1px solid #e5e7eb; display: grid; grid-template-columns: repeat(4, 1fr); z-index: 30; }
        .field-nav a { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; text-decoration: none; color: #64748b; font-size: 11px; font-weight: 600; }
        .field-nav a.active { color: #a855f7; }
        .field-nav i { width: 18px; height: 18px; }
        .stat-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 12px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 12px; border: 1px solid #e5e7eb; }
        .stat-card .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
        .stat-card .value { font-size: 20px; font-weight: 800; margin-top: 4px; }
        .shop-card, .list-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 10px; }
        .flash-ok { background: #e8f8ee; color: #15803d; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: 13px; }
        .flash-err { background: #fef2f2; color: #b91c1c; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: 13px; }
        .qty-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .qty-row input { width: 72px; text-align: center; }
        .btn-block { width: 100%; justify-content: center; }
        .open-banner { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; border-radius: 12px; padding: 12px; margin-bottom: 12px; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@0.469.0"></script>
</head>
<body>
    <div class="field-shell">
        <div class="field-top">
            <div style="flex:1;min-width:0">
                <h1>@yield('heading', 'Field')</h1>
                <div class="muted">{{ auth()->user()->name ?? '' }} · {{ $salesmanProfile->employee_code ?? '' }}</div>
            </div>
            <form action="{{ route('field.logout') }}" method="post">@csrf
                <button type="submit" class="logout-btn" style="color:#94a3b8" title="Sign out">
                    <i data-lucide="log-out" style="width:16px;height:16px"></i>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
        <div class="field-body">
            @if (session('success'))
                <div class="flash-ok">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash-err">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </div>
        <nav class="field-nav">
            <a href="{{ route('field.dashboard') }}" class="{{ request()->routeIs('field.dashboard') ? 'active' : '' }}"><i data-lucide="home"></i>Home</a>
            <a href="{{ route('field.shops') }}" class="{{ request()->routeIs('field.shops') ? 'active' : '' }}"><i data-lucide="store"></i>Shops</a>
            <a href="{{ route('field.orders') }}" class="{{ request()->routeIs('field.orders*') || request()->routeIs('field.visit*') ? 'active' : '' }}"><i data-lucide="shopping-bag"></i>Orders</a>
            <a href="{{ route('field.dashboard') }}" onclick="return false;" style="opacity:.45"><i data-lucide="map-pin"></i>Visits</a>
        </nav>
    </div>
    <script>document.addEventListener('DOMContentLoaded', () => window.lucide && lucide.createIcons());</script>
</body>
</html>
