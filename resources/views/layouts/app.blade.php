<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · Bynnas Trade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @include('partials.favicon')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@0.469.0"></script>
    <style>[x-cloak]{display:none !important}</style>
    @stack('head')
</head>
<body x-data="{
        sidebarOpen: window.innerWidth >= 1024,
        sidebarExpanded: localStorage.getItem('bt_sidebar_expanded') === '1',
        commandOpen: false,
        notifsOpen: false,
        toggleSidebar() {
            if (window.innerWidth < 1024) {
                this.sidebarOpen = !this.sidebarOpen;
                return;
            }
            this.sidebarExpanded = !this.sidebarExpanded;
            localStorage.setItem('bt_sidebar_expanded', this.sidebarExpanded ? '1' : '0');
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        }
      }"
      @keydown.ctrl.k.prevent="commandOpen = true"
      @keydown.meta.k.prevent="commandOpen = true"
      @keydown.escape="commandOpen = false; notifsOpen = false"
      @resize.window="if (window.innerWidth >= 1024) { sidebarOpen = true }">
    <div class="overlay" :class="sidebarOpen && window.innerWidth < 1024 && 'show'" @click="sidebarOpen = false"></div>
    <div class="app-shell">
        @include('layouts.partials.sidebar')
        <div class="main">
            @include('layouts.partials.header')
            <main class="page">
                @yield('content')
            </main>
        </div>
    </div>

    <div x-show="commandOpen" x-cloak class="overlay show" @click="commandOpen = false" style="display:block">
        <div class="card" style="max-width:560px;margin:12vh auto;padding:8px" @click.stop>
            <input class="input" style="width:100%;height:48px;border:0;font-size:15px" placeholder="Search shops, orders, shipments..." autofocus>
            <div style="padding:8px 12px" class="muted">Quick links</div>
            @foreach (['Dashboard' => 'dashboard', 'Orders' => 'orders.index', 'Shipments' => 'shipments.index', 'Inventory' => 'inventory.index', 'Users & Roles' => 'users.index'] as $label => $route)
                <a href="{{ route($route) }}" style="display:block;padding:10px 12px;border-radius:8px" onmouseover="this.style.background='#f8f9fd'">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
