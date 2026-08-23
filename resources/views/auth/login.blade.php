<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Bynnas Trade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:linear-gradient(180deg,#161a27 0%,#1c2130 60%,#f4f6fb 60%)">
    <div class="card" style="width:min(440px,92vw);padding:32px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <span class="brand-mark">
                <svg width="16" height="16" viewBox="0 0 24 24"><path d="M12 3 21 8.5v7L12 21 3 15.5v-7L12 3Z" fill="#fff"/></svg>
            </span>
            <strong style="font-size:18px">Bynnas Trade</strong>
        </div>
        <h1 style="margin:12px 0 6px;font-size:24px;letter-spacing:-0.03em">Private B2B portal</h1>
        <p class="muted" style="margin:0 0 22px">Sign in with the credentials issued by Super Admin. Shop owners and salesmen only see the records assigned to them.</p>

        @if ($errors->any())
            <div style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:14px">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="post">
            @csrf
            <label class="label">Work email</label>
            <input class="input" style="width:100%;margin-bottom:12px" type="email" name="email" value="{{ old('email', 'admin@bynnastrade.com') }}" required autocomplete="username">
            <label class="label">Password</label>
            <input class="input" style="width:100%;margin-bottom:16px" type="password" name="password" value="12345678" required autocomplete="current-password">
            <button class="btn btn-primary" style="width:100%;justify-content:center;height:44px" type="submit">Continue to dashboard</button>
        </form>
    </div>
</body>
</html>
