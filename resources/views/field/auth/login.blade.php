<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Field login · Bynnas Trade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @include('partials.favicon')
    <style>
        body { background: linear-gradient(160deg, var(--c30) 0%, var(--c30-elevated) 55%, #2A2640 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { width: 100%; max-width: 400px; background: var(--c30-soft); border-radius: 16px; padding: 28px 24px; box-shadow: var(--shadow); border: 1px solid var(--line); }
        .login-card h1 { margin: 0 0 4px; font-size: 22px; color: var(--text); }
        .login-card .sub { color: var(--muted); margin-bottom: 20px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-card">
        <x-brand-logo :size="48" show-wordmark />
        <h1 style="margin-top:16px">Field salesman</h1>
        <div class="sub">Mobile order collection · check in at shops</div>

        @if ($errors->any())
            <div style="background:#fef2f2;color:#b91c1c;padding:10px 12px;border-radius:8px;margin-bottom:14px;font-size:13px">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('field.login.submit') }}">
            @csrf
            <div class="field" style="margin-bottom:12px">
                <label class="label">Email</label>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field" style="margin-bottom:16px">
                <label class="label">Password</label>
                <input class="input" type="password" name="password" required>
            </div>
            <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:16px">
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
            <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
