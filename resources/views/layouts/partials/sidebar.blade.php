@php
    $nav = [
        ['label' => 'SALES & ORDERS', 'items' => [
            ['name' => 'shops.index', 'label' => 'Shops', 'icon' => 'store'],
            ['name' => 'salesmen.index', 'label' => 'Salesmen', 'icon' => 'users'],
            ['name' => 'orders.index', 'label' => 'Orders', 'icon' => 'shopping-bag'],
            ['name' => 'returns.index', 'label' => 'Returns', 'icon' => 'rotate-ccw'],
            ['name' => 'invoices.index', 'label' => 'Invoices', 'icon' => 'file-text'],
            ['name' => 'payments.index', 'label' => 'Payments', 'icon' => 'wallet'],
        ]],
        ['label' => 'PRODUCT & INVENTORY', 'items' => [
            ['name' => 'products.index', 'label' => 'Products', 'icon' => 'package'],
            ['name' => 'categories.index', 'label' => 'Categories', 'icon' => 'layers'],
            ['name' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'boxes'],
            ['name' => 'warehouses.index', 'label' => 'Warehouses', 'icon' => 'warehouse'],
            ['name' => 'shipments.index', 'label' => 'Shipments', 'icon' => 'ship', 'badge' => 'New'],
        ]],
        ['label' => 'PROCUREMENT', 'items' => [
            ['name' => 'suppliers.index', 'label' => 'Suppliers', 'icon' => 'factory'],
            ['name' => 'purchases.index', 'label' => 'Purchases', 'icon' => 'clipboard-list'],
        ]],
        ['label' => 'REPORTS & ANALYTICS', 'items' => [
            ['name' => 'reports.index', 'label' => 'Reports', 'icon' => 'file-bar-chart'],
            ['name' => 'analytics.index', 'label' => 'Analytics', 'icon' => 'trending-up'],
        ]],
        ['label' => 'SETTINGS', 'items' => [
            ['name' => 'users.index', 'label' => 'Users & Roles', 'icon' => 'shield'],
            ['name' => 'settings.index', 'label' => 'Settings', 'icon' => 'settings'],
        ]],
    ];
@endphp

<aside class="sidebar" :class="sidebarOpen && 'open'" @click.outside="if (window.innerWidth < 1024) sidebarOpen = false">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <span class="brand-mark">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M12 3 21 8.5v7L12 21 3 15.5v-7L12 3Z" fill="#fff"/>
            </svg>
        </span>
        Bynnas Trade
    </a>

    <div class="nav-scroll">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>

        @foreach ($nav as $group)
            <div class="nav-label">{{ $group['label'] }}</div>
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['name']) }}" class="nav-item {{ request()->routeIs(explode('.', $item['name'])[0].'.*') ? 'active' : '' }}">
                    <i data-lucide="{{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                    @if (!empty($item['badge']))
                        <span class="badge-new">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </div>

    <div class="sidebar-user">
        <div class="avatar">SA</div>
        <div style="min-width:0">
            <div style="color:#fff;font-weight:600;font-size:13px">Super Admin</div>
            <div style="font-size:12px;color:#8b93a7">Administrator</div>
        </div>
        <form action="{{ route('logout') }}" method="post" style="margin-left:auto">
            @csrf
            <button type="submit" style="background:none;border:0;color:#8b93a7;cursor:pointer;padding:0" title="Sign out">
                <i data-lucide="log-out" style="width:16px;height:16px"></i>
            </button>
        </form>
    </div>
</aside>
