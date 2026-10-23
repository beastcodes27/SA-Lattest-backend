<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Two-factor authentication · SmartAttend</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #F4F8F5; color: #1A1231; font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px; }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid rgba(32,15,53,.08); border-radius: 24px; padding: 28px; box-shadow: 0 18px 50px rgba(32,15,53,.08); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .mark { width: 44px; height: 44px; border-radius: 14px; background: #200F35; color: #fff; display: grid; place-items: center; font-weight: 800; }
        .brand b { display: block; font-size: 15px; letter-spacing: -.2px; }
        .brand small { color: #6B7280; font-size: 12px; }
        h1 { margin: 0 0 8px; font-size: 24px; letter-spacing: -.5px; }
        p { margin: 0 0 20px; color: #55576B; font-size: 13.5px; line-height: 1.6; }
        label { display: block; margin-bottom: 7px; color: #3B2E63; font-size: 11px; font-weight: 800; letter-spacing: .7px; text-transform: uppercase; }
        input { width: 100%; height: 56px; border-radius: 14px; border: 1px solid rgba(32,15,53,.16); background: #F8FAF9; padding: 0 16px; font: inherit; font-size: 22px; font-weight: 700; letter-spacing: 8px; text-align: center; color: #1A1231; }
        input:focus { outline: none; border-color: #200F35; box-shadow: 0 0 0 4px rgba(32,15,53,.08); background: #fff; }
        .error { margin: 10px 0 0; padding: 10px 12px; border-radius: 10px; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; font-size: 12.5px; line-height: 1.5; }
        .hint { margin-top: 10px; color: #6B7280; font-size: 12px; line-height: 1.5; }
        button { width: 100%; height: 54px; margin-top: 20px; border: 0; border-radius: 15px; background: #200F35; color: #fff; font: inherit; font-size: 14px; font-weight: 800; cursor: pointer; }
        button:hover { opacity: .92; }
        .back { display: block; margin-top: 16px; text-align: center; color: #6B7280; font-size: 12.5px; text-decoration: none; }
        .back:hover { color: #200F35; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            <div class="mark">SA</div>
            <div>
                <b>SmartAttend</b>
                <small>System administration</small>
            </div>
        </div>

        <h1>Two-factor authentication</h1>
        <p>Enter the 6-digit code from your authenticator app to finish signing in as <strong>{{ $employeeId }}</strong>.</p>

        <form method="POST" action="{{ route('portal.mfa.post') }}">
            @csrf
            <label for="code">Authentication code</label>
            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" autofocus required value="{{ old('code') }}">

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <div class="hint">Open your authenticator app and enter the current 6-digit code for this account.</div>
            <button type="submit">Verify and continue</button>
        </form>

        <a class="back" href="{{ route('portal.login') }}">Back to sign in</a>
    </main>
</body>
</html>
