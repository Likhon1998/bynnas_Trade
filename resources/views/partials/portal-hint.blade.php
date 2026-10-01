@if (session('portal_hint'))
    <a href="{{ session('portal_hint')['url'] }}"
       style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;box-sizing:border-box;min-height:44px;margin:-4px 0 14px;padding:10px 14px;border-radius:10px;background:#5B21B6;color:#fff;font-weight:600;font-size:14px;text-decoration:none">
        Go to {{ session('portal_hint')['label'] }} &rarr;
    </a>
@endif
