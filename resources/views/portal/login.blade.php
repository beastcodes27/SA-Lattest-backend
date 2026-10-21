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
            background: var(--bg);
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
            background: #1B0B2E;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand-top { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--emerald);
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
            background: var(--ink);
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

                <div style="display:flex;justify-content:flex-end;margin-top:8px;">
                    <a href="javascript:void(0)" onclick="openResetModal()" style="font-size:12.5px;font-weight:600;color:var(--accent);text-decoration:none;">Forgot password?</a>
                </div>

                <button type="submit">Sign In</button>
            </form>

            <div class="footnote">Protected access &middot; System administrators only</div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div id="resetBackdrop" style="display:none;position:fixed;inset:0;background:rgba(20,9,38,0.65);backdrop-filter:blur(4px);z-index:99;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#FFFFFF;border-radius:22px;width:100%;max-width:440px;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.3);position:relative;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 id="resetModalHeading" style="margin:0;font-size:20px;font-weight:800;color:var(--ink);">Reset Password</h3>
                <button type="button" onclick="closeResetModal()" style="margin:0;width:34px;height:34px;border-radius:17px;background:rgba(32,15,53,0.06);color:var(--ink);border:0;padding:0;display:flex;align-items:center;justify-content:center;font-size:18px;line-height:1;box-shadow:none;cursor:pointer;">&times;</button>
            </div>

            <div id="resetFeedback" style="display:none;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:14px;"></div>

            <!-- Step 1: Request Code -->
            <div id="resetStep1">
                <p style="margin:0 0 16px;font-size:13.5px;line-height:1.6;color:var(--muted);">
                    Enter your registered email address. We will verify your account and email a 6-digit reset code.
                </p>
                <label for="resetEmail" style="margin-top:0;">REGISTERED EMAIL</label>
                <input type="email" id="resetEmail" placeholder="e.g. name@company.com" style="margin-bottom:14px;">
                <button type="button" id="btnSendCode" onclick="handleSendResetCode()" style="margin-top:8px;">Send Reset Code</button>
            </div>

            <!-- Step 2: Verify Code & Set Password -->
            <div id="resetStep2" style="display:none;">
                <p id="resetStep2Notice" style="margin:0 0 16px;font-size:13.5px;line-height:1.6;color:var(--muted);"></p>
                
                <label for="resetCode" style="margin-top:0;">6-DIGIT CODE</label>
                <input type="text" id="resetCode" maxlength="6" placeholder="000000" style="letter-spacing:4px;font-size:16px;font-weight:700;margin-bottom:12px;">

                <label for="resetNewPass">NEW PASSWORD</label>
                <input type="password" id="resetNewPass" placeholder="Minimum 6 characters" style="margin-bottom:12px;">

                <label for="resetConfirmPass">CONFIRM PASSWORD</label>
                <input type="password" id="resetConfirmPass" placeholder="Repeat new password" style="margin-bottom:14px;">

                <button type="button" id="btnSubmitReset" onclick="handleSubmitReset()" style="margin-top:8px;">Confirm & Reset Password</button>
                <div style="text-align:center;margin-top:14px;">
                    <a href="javascript:void(0)" onclick="goToResetStep1()" style="font-size:12.5px;font-weight:600;color:var(--muted);text-decoration:none;">Change Email</a>
                </div>
            </div>

            <!-- Step 3: Success -->
            <div id="resetStep3" style="display:none;text-align:center;padding:12px 0;">
                <div style="width:60px;height:60px;border-radius:30px;background:#10B981;color:#FFFFFF;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:14px;">&#10003;</div>
                <h4 style="margin:0 0 8px;font-size:18px;font-weight:800;color:var(--ink);">Password Reset Complete!</h4>
                <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:var(--muted);">You can now sign in with your new password.</p>
                <button type="button" onclick="closeResetModal()" style="margin-top:0;">Proceed to Sign In</button>
            </div>
        </div>
    </div>

    <script>
        const resetBackdrop = document.getElementById('resetBackdrop');
        const resetFeedback = document.getElementById('resetFeedback');
        const resetStep1 = document.getElementById('resetStep1');
        const resetStep2 = document.getElementById('resetStep2');
        const resetStep3 = document.getElementById('resetStep3');
        const btnSendCode = document.getElementById('btnSendCode');
        const btnSubmitReset = document.getElementById('btnSubmitReset');

        function openResetModal() {
            resetBackdrop.style.display = 'flex';
            goToResetStep1();
            document.getElementById('resetEmail').focus();
        }

        function closeResetModal() {
            resetBackdrop.style.display = 'none';
            hideFeedback();
        }

        function goToResetStep1() {
            resetStep1.style.display = 'block';
            resetStep2.style.display = 'none';
            resetStep3.style.display = 'none';
            hideFeedback();
        }

        function showFeedback(msg, isSuccess) {
            resetFeedback.textContent = msg;
            resetFeedback.style.display = 'block';
            if (isSuccess) {
                resetFeedback.style.background = 'rgba(16, 185, 129, 0.1)';
                resetFeedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                resetFeedback.style.color = '#0F7B4A';
            } else {
                resetFeedback.style.background = 'rgba(239, 68, 68, 0.1)';
                resetFeedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                resetFeedback.style.color = '#DC2626';
            }
        }

        function hideFeedback() {
            resetFeedback.style.display = 'none';
        }

        async function handleSendResetCode() {
            const email = (document.getElementById('resetEmail').value || '').trim();
            if (!email || !email.includes('@')) {
                showFeedback('Please enter a valid registered email address.', false);
                return;
            }

            btnSendCode.disabled = true;
            btnSendCode.textContent = 'Checking email...';
            hideFeedback();

            try {
                const res = await fetch('/api/auth/forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: email })
                });
                const data = await res.json();

                if (!res.ok) {
                    showFeedback(data.message || 'Email is not registered.', false);
                    btnSendCode.disabled = false;
                    btnSendCode.textContent = 'Send Reset Code';
                    return;
                }

                resetStep1.style.display = 'none';
                resetStep2.style.display = 'block';
                document.getElementById('resetStep2Notice').textContent = 'We sent a 6-digit code to ' + email + '. Enter the code below to set your new password.';
                showFeedback(data.message || 'Verification code sent.', true);
            } catch (err) {
                showFeedback('Network error. Please try again.', false);
            } finally {
                btnSendCode.disabled = false;
                btnSendCode.textContent = 'Send Reset Code';
            }
        }

        async function handleSubmitReset() {
            const email = (document.getElementById('resetEmail').value || '').trim();
            const code = (document.getElementById('resetCode').value || '').trim();
            const password = document.getElementById('resetNewPass').value;
            const confirm = document.getElementById('resetConfirmPass').value;

            if (code.length !== 6) {
                showFeedback('Please enter the complete 6-digit code.', false);
                return;
            }
            if (password.length < 6) {
                showFeedback('New password must be at least 6 characters.', false);
                return;
            }
            if (password !== confirm) {
                showFeedback('Passwords do not match.', false);
                return;
            }

            btnSubmitReset.disabled = true;
            btnSubmitReset.textContent = 'Resetting password...';
            hideFeedback();

            try {
                const res = await fetch('/api/auth/reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        email: email,
                        code: code,
                        password: password,
                        password_confirmation: confirm
                    })
                });
                const data = await res.json();

                if (!res.ok) {
                    showFeedback(data.message || 'Failed to reset password.', false);
                    btnSubmitReset.disabled = false;
                    btnSubmitReset.textContent = 'Confirm & Reset Password';
                    return;
                }

                resetStep2.style.display = 'none';
                resetStep3.style.display = 'block';
                hideFeedback();
            } catch (err) {
                showFeedback('Network error. Please try again.', false);
                btnSubmitReset.disabled = false;
                btnSubmitReset.textContent = 'Confirm & Reset Password';
            }
        }
    </script>
</body>
</html>
