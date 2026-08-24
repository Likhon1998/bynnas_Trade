<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Field login · Bynnas Trade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        body { background: linear-gradient(160deg, #0f172a 0%, #1e293b 45%, #334155 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { width: 100%; max-width: 400px; background: #fff; border-radius: 16px; padding: 28px 24px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
        .login-card h1 { margin: 0 0 4px; font-size: 22px; }
        .login-card .sub { color: #64748b; margin-bottom: 20px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="font-size:12px;font-weight:700;color:#ff3b30;letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px">Bynnas Trade</div>
        <h1>Field salesman</h1>
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
