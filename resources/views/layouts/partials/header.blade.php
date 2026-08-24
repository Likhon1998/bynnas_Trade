@php
    $authUser = auth()->user();
    $unreadNotifications = $authUser?->unreadNotifications()->latest()->limit(8)->get() ?? collect();
    $unreadCount = $authUser?->unreadNotifications()->count() ?? 0;
@endphp
<header class="topbar">
    <div class="search-wrap">
        <button class="icon-btn" type="button" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle navigation">
            <i data-lucide="menu"></i>
        </button>
        <label class="search">
            <i data-lucide="search" style="width:16px;height:16px"></i>
            <input type="search" placeholder="Search anything..." @focus="commandOpen = true">
            <span class="kbd">Ctrl + K</span>
        </label>
    </div>

    <div style="display:flex;align-items:center;gap:8px">
        <button class="icon-btn" type="button" @click="notifsOpen = !notifsOpen">
            <i data-lucide="bell"></i>
            @if ($unreadCount > 0)
                <span class="dot-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            @endif
        </button>
        <button class="icon-btn" type="button" onclick="document.documentElement.requestFullscreen?.()">
            <i data-lucide="maximize-2"></i>
        </button>
        <div style="display:flex;align-items:center;gap:10px;margin-left:8px">
            <div class="avatar">{{ strtoupper(substr($authUser?->name ?? 'SA', 0, 2)) }}</div>
            <div>
                <div style="font-size:13px;font-weight:700">{{ $authUser?->name ?? 'Admin' }}</div>
                <div class="muted" style="font-size:12px">{{ $authUser?->roles?->first()?->name ?? 'Administrator' }}</div>
            </div>
        </div>
    </div>

    <div x-show="notifsOpen" x-cloak @click.outside="notifsOpen = false" class="card" style="position:absolute;right:24px;top:68px;width:360px;padding:8px 0;z-index:50">
        <div style="padding:10px 16px;display:flex;justify-content:space-between;align-items:center">
            <div style="font-weight:700">Notifications</div>
            <a href="{{ route('notifications.index') }}" class="muted" style="font-size:12px">View all</a>
        </div>
        @forelse ($unreadNotifications as $n)
            @php $data = $n->data; @endphp
            <form method="post" action="{{ route('notifications.read', $n->id) }}" style="margin:0">
                @csrf
                <button type="submit" style="display:block;width:100%;text-align:left;padding:10px 16px;border:0;border-top:1px solid #f1f3f8;background:transparent;cursor:pointer">
                    <div style="font-size:13px;font-weight:600">{{ $data['title'] ?? 'Alert' }}</div>
                    <div class="muted">{{ \Illuminate\Support\Str::limit($data['body'] ?? '', 80) }}</div>
                </button>
            </form>
        @empty
            <div class="muted" style="padding:14px 16px;border-top:1px solid #f1f3f8">You're all caught up.</div>
        @endforelse
    </div>
</header>
