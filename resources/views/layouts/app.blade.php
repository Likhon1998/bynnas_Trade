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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@0.469.0"></script>
    <style>[x-cloak]{display:none !important}</style>
    @stack('head')
    {{-- Register before Alpine loads so adminShell exists on boot --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminShell', () => {
                const stored = localStorage.getItem('bt_sidebar_v2');
                return {
                    isDesktop: window.innerWidth >= 1025,
                    sidebarOpen: window.innerWidth >= 1025,
                    sidebarExpanded: stored !== '0',
                    commandOpen: false,
                    notifsOpen: false,
                    toggleSidebar() {
                        if (!this.isDesktop) {
                            this.sidebarOpen = !this.sidebarOpen;
                            return;
                        }
                        this.sidebarExpanded = !this.sidebarExpanded;
                        localStorage.setItem('bt_sidebar_v2', this.sidebarExpanded ? '1' : '0');
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },
                    expandSidebar() {
                        this.sidebarExpanded = true;
                        localStorage.setItem('bt_sidebar_v2', '1');
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },
                    onResize() {
                        this.isDesktop = window.innerWidth >= 1025;
                        if (this.isDesktop) this.sidebarOpen = true;
                    },
                };
            });
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body x-data="adminShell"
      @keydown.ctrl.k.prevent="commandOpen = true"
      @keydown.meta.k.prevent="commandOpen = true"
      @keydown.escape="commandOpen = false; notifsOpen = false"
      @resize.window="onResize()">
    <div class="overlay" :class="sidebarOpen && !isDesktop && 'show'" @click="sidebarOpen = false"></div>
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
