<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SmartAttend Admin &middot; Sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #EAF7EF;
            --ink: #200F35;
            --accent: #7C3AED;
            --emerald: #10B981;
            --text: #1A1231;
            --muted: #5B5A73;
            --red: #EF4444;
            --card-border: rgba(32, 15, 53, 0.08);
            --shadow-lg: 0 30px 80px rgba(24, 9, 44, 0.22);
            --ring: 0 0 0 3px rgba(124, 58, 237, 0.18);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(900px 520px at 105% -10%, rgba(124, 58, 237, 0.18), transparent 60%),
                radial-gradient(820px 500px at -10% 110%, rgba(16, 185, 129, 0.20), transparent 58%),
                linear-gradient(180deg, #F4FBF7 0%, var(--bg) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .shell {
            width: 100%;
            max-width: 940px;
            background: #fff;
            border: 1px solid var(--card-border);
            border-radius: 26px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
        }

        /* Brand panel */
        .brand-panel {
            position: relative;
            padding: 44px 40px;
            color: #fff;
            background: linear-gradient(165deg, #28124A 0%, #190830 58%, #120420 100%);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            width: 360px;
            height: 360px;
            right: -140px;
            bottom: -160px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.35), transparent 68%);
        }
        .brand-panel::before {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            left: -120px;
            top: -120px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.4), transparent 70%);
        }
        .brand-top { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #34D399 0%, #10B981 48%, #7C3AED 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B1F16;
            font-weight: 900;
            font-size: 18px;
            letter-spacing: -0.5px;
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.4);
        }
        .brand-name { font-size: 18px; font-weight: 800; letter-spacing: -0.3px; }
        .brand-sub { font-size: 11px; font-weight: 700; color: #6EE7B7; text-transform: uppercase; letter-spacing: 0.9px; }
        .brand-hero { position: relative; z-index: 1; margin: 40px 0; }
        .brand-hero h1 { font-size: 28px; line-height: 1.25; font-weight: 800; margin: 0 0 12px; letter-spacing: -0.6px; }
        .brand-hero p { color: rgba(255, 255, 255, 0.66); font-size: 14px; line-height: 1.6; margin: 0; max-width: 30ch; }
        .feat-list { position: relative; z-index: 1; list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
        .feat-list li { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: rgba(255, 255, 255, 0.82); }
        .feat-dot {
            width: 22px;
            height: 22px;
            border-radius: 8px;
            background: rgba(110, 231, 183, 0.14);
            border: 1px solid rgba(110, 231, 183, 0.32);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6EE7B7;
            font-size: 12px;
            flex-shrink: 0;
        }

        /* Form panel */
        .form-panel { padding: 46px 44px; display: flex; flex-direction: column; justify-content: center; }
        .form-panel h2 { margin: 0; font-size: 23px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; }
        .form-panel .tagline { color: var(--muted); font-size: 13.5px; margin: 6px 0 26px; }
        label { display: block; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.7px; margin: 16px 0 7px; }
        input {
            width: 100%;
            padding: 13px 15px;
            border-radius: 12px;
            border: 1px solid rgba(32, 15, 53, 0.16);
            font-size: 15px;
            font-family: inherit;
            color: var(--text);
            background: #FCFDFE;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus { outline: none; border-color: var(--accent); box-shadow: var(--ring); background: #fff; }
        .error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.32);
            color: #DC2626;
            border-radius: 11px;
            padding: 11px 13px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        button {
            margin-top: 26px;
            width: 100%;
            background: linear-gradient(135deg, #2A1148, #1B0B2E);
            color: #fff;
            border: 0;
            border-radius: 13px;
            padding: 15px;
            font-size: 15px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            letter-spacing: 0.2px;
            box-shadow: 0 12px 26px rgba(32, 15, 53, 0.28);
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 16px 34px rgba(32, 15, 53, 0.34); }
        button:active { transform: translateY(0); }
        .footnote { text-align: center; color: var(--muted); font-size: 11.5px; margin-top: 22px; }

        @media (max-width: 780px) {
            .shell { grid-template-columns: 1fr; max-width: 420px; }
            .brand-panel { padding: 30px 28px; }
            .brand-hero { margin: 24px 0 18px; }
            .brand-hero h1 { font-size: 22px; }
            .feat-list { display: none; }
            .form-panel { padding: 34px 28px 40px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="brand-panel">
            <div class="brand-top">
                <div class="brand-logo">SA</div>
                <div>
                    <div class="brand-name">SmartAttend</div>
                    <div class="brand-sub">System Console</div>
                </div>
            </div>

            <div class="brand-hero">
                <h1>System administration,<br>beautifully controlled.</h1>
                <p>Manage organizations, subscriptions, packages and push broadcasts from one secure console.</p>
            </div>

            <ul class="feat-list">
                <li><span class="feat-dot">&#10003;</span> Approve &amp; onboard organizations</li>
                <li><span class="feat-dot">&#10003;</span> Manage plans, trials &amp; promos</li>
                <li><span class="feat-dot">&#10003;</span> Broadcast live push notifications</li>
            </ul>
        </div>

        <div class="form-panel">
            <h2>Welcome back</h2>
            <div class="tagline">Sign in to the System Administration portal.</div>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('portal.login.post') }}">
                @csrf
                <label for="employee_id">Admin ID</label>
                <input id="employee_id" name="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. SYS-ADMIN1" autofocus autocomplete="username" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Your password" autocomplete="current-password" required>

                <button type="submit">Sign In</button>
            </form>

            <div class="footnote">Protected access &middot; System administrators only</div>
        </div>
    </div>
</body>
</html>
