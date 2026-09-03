@php
    $authUser = auth()->user();
    $unreadNotifications = $authUser?->unreadNotifications()->latest()->limit(8)->get() ?? collect();
    $unreadCount = $authUser?->unreadNotifications()->count() ?? 0;
@endphp
<header class="topbar">
    <div class="search-wrap">
        <button class="icon-btn" type="button" @click="toggleSidebar()" aria-label="Toggle navigation" title="Toggle sidebar">
            <i data-lucide="menu"></i>
        </button>
        <label class="search">
            <i data-lucide="search" style="width:16px;height:16px"></i>
            <input type="search" placeholder="Search anything..." @focus="commandOpen = true">
            <span class="kbd">Ctrl + K</span>
        </label>
    </div>

    <div style="display:flex;align-items:center;gap:8px;position:relative">
        <button class="icon-btn" type="button" @click="notifsOpen = !notifsOpen" aria-label="Notifications" title="Notifications">
            <i data-lucide="bell"></i>
            @if ($unreadCount > 0)
                <span class="dot-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            @endif
        </button>
        <button class="icon-btn" type="button" onclick="document.documentElement.requestFullscreen?.()" aria-label="Fullscreen">
            <i data-lucide="maximize-2"></i>
        </button>
        <div style="display:flex;align-items:center;gap:10px;margin-left:8px">
            <div class="avatar">{{ strtoupper(substr($authUser?->name ?? 'SA', 0, 2)) }}</div>
            <div>
                <div style="font-size:13px;font-weight:700">{{ $authUser?->name ?? 'Admin' }}</div>
                <div class="muted" style="font-size:12px">{{ $authUser?->roles?->first()?->name ?? 'Administrator' }}</div>
            </div>
        </div>

        <div x-show="notifsOpen" x-cloak @click.outside="notifsOpen = false" class="card notif-panel">
            <div style="padding:10px 16px;display:flex;justify-content:space-between;align-items:center;gap:8px">
                <div style="font-weight:700">Notifications @if($unreadCount > 0)<span class="muted" style="font-weight:500">({{ $unreadCount }})</span>@endif</div>
                <div style="display:flex;gap:10px;align-items:center">
                    @if ($unreadCount > 0)
                        <form method="post" action="{{ route('notifications.read-all') }}" style="margin:0">
                            @csrf
                            <button type="submit" class="muted" style="border:0;background:none;cursor:pointer;font-size:12px;padding:0">Mark all read</button>
                        </form>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="muted" style="font-size:12px">View all</a>
                </div>
            </div>
            @forelse ($unreadNotifications as $n)
                @php $data = $n->data; @endphp
                <form method="post" action="{{ route('notifications.read', $n->id) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="notif-item">
                        <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $data['title'] ?? 'Alert' }}</div>
                        <div class="muted">{{ \Illuminate\Support\Str::limit($data['body'] ?? '', 90) }}</div>
                        <div class="muted" style="font-size:11px;margin-top:4px">{{ $n->created_at?->diffForHumans() }} · {{ $data['category'] ?? 'system' }}</div>
                    </button>
                </form>
            @empty
                <div class="muted" style="padding:14px 16px;border-top:1px solid #f1f3f8">You're all caught up.</div>
            @endforelse
        </div>
    </div>
</header>
