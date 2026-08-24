<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>B2B Partner Login · Bynnas Trade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:linear-gradient(180deg,#161a27 0%,#1c2130 55%,#f4f6fb 55%)">
    <div class="card" style="width:min(440px,92vw);padding:32px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <span class="brand-mark"><svg width="16" height="16" viewBox="0 0 24 24"><path d="M12 3 21 8.5v7L12 21 3 15.5v-7L12 3Z" fill="#fff"/></svg></span>
            <strong style="font-size:18px">Bynnas Trade</strong>
        </div>
        <h1 style="margin:12px 0 6px;font-size:24px;letter-spacing:-0.03em">B2B Partner Login</h1>
        <p class="muted" style="margin:0 0 22px">Approved shop owners only. Wholesale prices and stock are private to your account.</p>
        @if ($errors->any())
            <div style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:14px">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('portal.login.submit') }}" method="post">
            @csrf
            <label class="label">Work email</label>
            <input class="input" style="width:100%;margin-bottom:12px" type="email" name="email" value="{{ old('email') }}" required>
            <label class="label">Password</label>
            <input class="input" style="width:100%;margin-bottom:16px" type="password" name="password" required>
            <button class="btn btn-primary" style="width:100%;justify-content:center;height:44px" type="submit">Enter shop portal</button>
        </form>
        <p class="muted" style="margin-top:16px;text-align:center"><a href="{{ route('login') }}">Admin login</a></p>
    </div>
</body>
</html>
