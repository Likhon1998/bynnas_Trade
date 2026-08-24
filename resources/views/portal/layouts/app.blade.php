<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Shop Portal') · Bynnas Trade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@0.469.0"></script>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a href="{{ route('portal.dashboard') }}" class="sidebar-brand">
                <span class="brand-mark">
                    <svg width="16" height="16" viewBox="0 0 24 24"><path d="M12 3 21 8.5v7L12 21 3 15.5v-7L12 3Z" fill="#fff"/></svg>
                </span>
                B2B Portal
            </a>
            <div class="nav-scroll">
                <a href="{{ route('portal.dashboard') }}" class="nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i> Dashboard
                </a>
                <a href="{{ route('portal.products') }}" class="nav-item {{ request()->routeIs('portal.products') ? 'active' : '' }}">
                    <i data-lucide="package"></i> Products
                </a>
                <a href="{{ route('portal.orders') }}" class="nav-item {{ request()->routeIs('portal.orders*') ? 'active' : '' }}">
                    <i data-lucide="shopping-bag"></i> Orders
                </a>
                <a href="{{ route('portal.profile') }}" class="nav-item {{ request()->routeIs('portal.profile') ? 'active' : '' }}">
                    <i data-lucide="store"></i> My shop
                </a>
            </div>
            <div class="sidebar-user">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'SH', 0, 2)) }}</div>
                <div style="min-width:0">
                    <div style="color:#fff;font-weight:600;font-size:13px">{{ $currentShop->name ?? 'Shop' }}</div>
                    <div style="font-size:12px;color:#8b93a7">{{ auth()->user()->name }}</div>
                </div>
                <form action="{{ route('portal.logout') }}" method="post" style="margin-left:auto">@csrf
                    <button type="submit" style="background:none;border:0;color:#8b93a7;cursor:pointer;padding:0"><i data-lucide="log-out" style="width:16px;height:16px"></i></button>
                </form>
            </div>
        </aside>
        <div class="main">
            <header class="topbar">
                <div>
                    <div style="font-weight:700">{{ $currentShop->name ?? 'Shop portal' }}</div>
                    <div class="muted">Private wholesale access · prices are scoped to your account</div>
                </div>
            </header>
            <main class="page">@yield('content')</main>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded', () => window.lucide && lucide.createIcons());</script>
</body>
</html>
