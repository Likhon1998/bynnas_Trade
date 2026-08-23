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
            <span class="dot-badge">8</span>
        </button>
        <button class="icon-btn" type="button">
            <i data-lucide="mail"></i>
            <span class="dot-badge">3</span>
        </button>
        <button class="icon-btn" type="button" onclick="document.documentElement.requestFullscreen?.()">
            <i data-lucide="maximize-2"></i>
        </button>
        <div style="display:flex;align-items:center;gap:10px;margin-left:8px">
            <div class="avatar">SA</div>
            <div>
                <div style="font-size:13px;font-weight:700">Super Admin</div>
                <div class="muted" style="font-size:12px">Administrator</div>
            </div>
        </div>
    </div>

    <div x-show="notifsOpen" x-cloak @click.outside="notifsOpen = false" class="card" style="position:absolute;right:24px;top:68px;width:360px;padding:8px 0;z-index:50">
        <div style="padding:10px 16px;font-weight:700">Notifications</div>
        <a href="{{ route('orders.index') }}" style="display:block;padding:10px 16px;border-top:1px solid #f1f3f8">
            <div style="font-size:13px;font-weight:600">2 orders awaiting Super Admin audit</div>
            <div class="muted">Tech Zone and Smart Plaza</div>
        </a>
        <a href="{{ route('shipments.index') }}" style="display:block;padding:10px 16px;border-top:1px solid #f1f3f8">
            <div style="font-size:13px;font-weight:600">SH-2505-002 is in customs</div>
            <div class="muted">Guangzhou, China</div>
        </a>
        <a href="{{ route('payments.index') }}" style="display:block;padding:10px 16px;border-top:1px solid #f1f3f8">
            <div style="font-size:13px;font-weight:600">bKash collection pending clearance</div>
            <div class="muted">PAY-9014 · City Gadgets</div>
        </a>
    </div>
</header>
