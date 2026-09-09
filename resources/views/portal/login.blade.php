<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SmartAttend Admin &middot; Sign in</title>
    <style>
        :root { --bg:#D7FFE0; --ink:#200F35; --text:#1A1231; --muted:#55576B; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; align-items:center; justify-content:center; }
        .card { background:#fff; border:1px solid rgba(32,15,53,.1); border-radius:20px; padding:36px; width:100%; max-width:380px; box-shadow:0 18px 40px rgba(32,15,53,.12); }
        .brand { font-size:20px; font-weight:800; color:var(--ink); }
        .tagline { color:var(--muted); font-size:13px; margin:4px 0 22px; }
        label { display:block; font-size:12px; font-weight:700; color:var(--ink); text-transform:uppercase; letter-spacing:.6px; margin:14px 0 6px; }
        input { width:100%; padding:12px 14px; border-radius:10px; border:1px solid rgba(32,15,53,.2); font-size:15px; }
        .error { background:rgba(194,48,48,.08); border:1px solid rgba(194,48,48,.35); color:#C23030; border-radius:10px; padding:10px 12px; font-size:13px; margin-top:12px; }
        button { margin-top:22px; width:100%; background:var(--ink); color:#D7FFE0; border:0; border-radius:12px; padding:14px; font-size:15px; font-weight:800; cursor:pointer; }
        button:hover { opacity:.9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">SmartAttend</div>
        <div class="tagline">System administration portal</div>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('portal.login.post') }}">
            @csrf
            <label for="employee_id">Admin ID</label>
            <input id="employee_id" name="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. SYS-ADMIN1" autofocus required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="Your password" required>

            <button type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>
