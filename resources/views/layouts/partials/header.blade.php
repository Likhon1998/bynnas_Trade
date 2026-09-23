@php
    $authUser = auth()->user();
    $bellNotifications = $authUser?->notifications()->latest()->limit(10)->get() ?? collect();
    $unreadCount = $authUser?->unreadNotifications()->count() ?? 0;
    $roleName = $authUser?->roles?->first()?->name;
    $displayName = $authUser?->name ?? 'Admin';
    $showRole = $roleName && strcasecmp($roleName, $displayName) !== 0;
@endphp
<header class="topbar">
    <div class="search-wrap">
        <button class="icon-btn" type="button" @click="toggleSidebar()" aria-label="Toggle navigation" title="Toggle sidebar">
            <i data-lucide="menu"></i>
        </button>
        <label class="search" title="Search (Ctrl+K)">
            <i data-lucide="search"></i>
            <input type="search" placeholder="Search…" @focus="commandOpen = true" aria-label="Search">
            <span class="kbd">⌘K</span>
        </label>
    </div>

    <div class="topbar-actions">
        <button class="icon-btn" type="button" @click="notifsOpen = !notifsOpen" aria-label="Notifications" title="Notifications">
            <i data-lucide="bell"></i>
            @if ($unreadCount > 0)
                <span class="dot-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            @endif
        </button>
        <div class="topbar-user" title="{{ $displayName }}{{ $showRole ? ' · '.$roleName : '' }}">
            <div class="avatar">{{ strtoupper(substr($displayName, 0, 2)) }}</div>
            <div class="topbar-user-meta">
                <span>{{ $displayName }}</span>
                @if ($showRole)
                    <em>{{ $roleName }}</em>
                @endif
            </div>
        </div>

        <div x-show="notifsOpen" x-cloak @click.outside="notifsOpen = false" class="card notif-panel">
            <div class="notif-panel-head">
                <strong>Recent @if($unreadCount > 0)<span class="muted">({{ $unreadCount }} unread)</span>@endif</strong>
                <div class="notif-panel-links">
                    @if ($unreadCount > 0)
                        <form method="post" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="muted">Mark all</button>
                        </form>
                    @endif
                </div>
            </div>
            @forelse ($bellNotifications as $n)
                @php $data = $n->data; @endphp
                <form method="post" action="{{ route('notifications.read', $n->id) }}">
                    @csrf
                    <button type="submit" class="notif-item {{ $n->read_at ? '' : 'is-unread' }}">
                        <div class="notif-item-title">{{ $data['title'] ?? 'Alert' }}</div>
                        <div class="muted">{{ \Illuminate\Support\Str::limit($data['body'] ?? '', 80) }}</div>
                        <div class="muted notif-item-meta">
                            {{ $n->created_at?->timezone(config('app.timezone'))->format('d M · h:i A') }}
                            · {{ $data['category'] ?? 'system' }}
                        </div>
                    </button>
                </form>
            @empty
                <div class="muted notif-empty">No notifications yet.</div>
            @endforelse
            <div class="notif-panel-foot">
                <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">
                    View all notification history
                </a>
            </div>
        </div>
    </div>
</header>
