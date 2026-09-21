<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartAttend &middot; System Admin Console</title>
    <style>
        :root {
            --bg: #D7FFE0;
            --ink: #200F35;
            --text: #1A1231;
            --muted: #55576B;
            --green: #1F7A43;
            --amber: #B8860B;
            --red: #C23030;
            --sidebar-bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --border: rgba(32, 15, 53, 0.10);
            --border-strong: rgba(32, 15, 53, 0.18);
            --active-bg: rgba(32, 15, 53, 0.08);
            --sidebar-width: 270px;
            --header-height: 64px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* SIDEBAR (Desktop/Laptop layout) */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 16px;
            z-index: 120;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 2px 0 16px rgba(32, 15, 53, 0.03);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 8px 18px;
            border-bottom: 1px solid var(--border);
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--ink);
            color: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(32, 15, 53, 0.15);
        }
        .brand-text {
            font-size: 16px;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.2;
        }
        .brand-sub {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 0.3px;
        }

        .user-badge-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(32, 15, 53, 0.04);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 10px 12px;
            margin: 16px 0 12px;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--ink);
            color: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .user-info {
            flex: 1;
            min-width: 0;
        }
        .user-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 10px 6px;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 12px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: 0;
            background: transparent;
            text-align: left;
            width: 100%;
            transition: all 0.15s ease;
            position: relative;
        }
        .sidebar-item:hover {
            background: var(--active-bg);
            color: var(--ink);
        }
        .sidebar-item.active {
            background: var(--ink);
            color: var(--bg);
            box-shadow: 0 4px 12px rgba(32, 15, 53, 0.14);
        }
        .sidebar-item.active svg {
            stroke: var(--bg);
            color: var(--bg);
        }
        .sidebar-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
        }
        .sidebar-item.active .sidebar-badge {
            background: var(--bg);
            color: var(--ink);
        }

        .sidebar-broadcast-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--ink);
            color: var(--bg);
            border: 0;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            margin: 12px 0 8px;
            box-shadow: 0 4px 10px rgba(32, 15, 53, 0.12);
            transition: opacity 0.15s;
        }
        .sidebar-broadcast-btn:hover {
            opacity: 0.88;
        }

        .sidebar-footer {
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .signout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: rgba(194, 48, 48, 0.08);
            color: var(--red);
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.15s;
        }
        .signout-btn:hover {
            background: rgba(194, 48, 48, 0.16);
        }

        /* MOBILE TOP BAR */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
            z-index: 110;
            box-shadow: 0 2px 8px rgba(32, 15, 53, 0.04);
        }
        .hamburger-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(32, 15, 53, 0.05);
            border: 1px solid var(--border);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .mobile-brand {
            font-size: 16px;
            font-weight: 800;
            color: var(--ink);
        }

        /* BACKDROP */
        .backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(32, 15, 53, 0.45);
            z-index: 115;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .backdrop.open {
            display: block;
            opacity: 1;
        }

        /* MAIN CONTENT AREA */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 32px 36px 80px;
            transition: margin-left 0.25s ease;
        }
        .content-wrap {
            max-width: 1020px;
            margin: 0 auto;
        }

        /* RESPONSIVE BREAKPOINT */
        @media (max-width: 899px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 8px 0 24px rgba(0, 0, 0, 0.25);
            }
            .mobile-header {
                display: flex;
            }
            .main-content {
                margin-left: 0;
                padding: calc(var(--header-height) + 16px) 16px 60px;
            }
        }

        /* SHARED UI COMPONENTS */
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            margin: 0;
        }
        .page-subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-top: 2px;
            font-weight: 600;
        }

        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:24px; }
        .stat { background:#fff; border:1px solid var(--border); border-radius:16px; padding:16px; box-shadow:0 2px 6px rgba(32,15,53,.02); }
        .stat b { font-size:26px; color:var(--ink); display:block; }
        .stat span { color:var(--muted); font-size:12px; font-weight:700; margin-top:2px; display:block; }
        .stat-btn { display:block; width:100%; text-align:left; font:inherit; cursor:pointer; transition:opacity .15s; }
        .stat-btn:hover { opacity:.75; }

        .filters { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .filter { border:1px solid var(--border-strong); background:#fff; border-radius:999px; padding:8px 16px; cursor:pointer; font-weight:700; font-size:13px; color:var(--muted); }
        .filter.active { background:var(--ink); color:var(--bg); border-color:var(--ink); }

        .org { background:#fff; border:1px solid var(--border); border-radius:18px; padding:18px 20px; margin-bottom:14px; box-shadow:0 2px 8px rgba(32,15,53,.02); }
        .org h3 { margin:0 0 4px; font-size:16px; font-weight:800; color:var(--ink); }
        .org .contact { color:var(--muted); font-size:12px; margin-bottom:10px; }
        .meta { display:flex; gap:14px; font-size:12px; color:var(--muted); flex-wrap:wrap; }
        .pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:800; }
        .actions { display:flex; gap:8px; margin-top:14px; flex-wrap:wrap; }
        
        .btn { border:0; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:800; cursor:pointer; transition:opacity .15s; }
        .btn:hover { opacity:.88; }
        .btn.primary { background:var(--ink); color:var(--bg); }
        .btn.danger { background:rgba(194,48,48,.1); color:var(--red); }
        .btn.ghost { background:rgba(32,15,53,.05); color:var(--ink); }
        .btn[disabled]{ opacity:.5; cursor:default; }

        .msg { position:fixed; left:50%; transform:translateX(-50%); top:20px; border-radius:12px; padding:12px 22px; font-weight:700; color:#fff; box-shadow:0 10px 30px rgba(0,0,0,.2); display:none; z-index:999; }
        .msg.ok { background:var(--green); }
        .msg.err { background:var(--red); }

        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
        .grid span { display:block; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        .grid b { font-size:13px; word-break:break-word; }

        .branch { background:rgba(32,15,53,.04); border:1px solid var(--border); border-radius:10px; padding:10px 12px; font-size:13px; font-weight:800; margin-bottom:8px; }
        .branch span { font-weight:400; color:var(--muted); font-size:12px; }

        table.emps { width:100%; border-collapse:collapse; font-size:13px; }
        table.emps th { text-align:left; background:rgba(32,15,53,.06); padding:9px 12px; color:var(--ink); font-size:11px; text-transform:uppercase; font-weight:800; }
        table.emps td { padding:10px 12px; border-bottom:1px solid rgba(32,15,53,.08); }

        select { padding:8px 10px; border-radius:8px; border:1px solid var(--border-strong); background:#fff; }
        .pfield { width:100%; padding:9px 12px; border-radius:10px; border:1px solid var(--border-strong); font-size:13px; margin-top:4px; background:#fff; font-family:inherit; }
        .plabel { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--ink); margin-top:10px; }
        .switch { display:inline-flex; align-items:center; gap:8px; font-weight:800; font-size:13px; cursor:pointer; margin-top:10px; }
    </style>
</head>
<body>

    <!-- MOBILE TOP HEADER -->
    <div class="mobile-header">
        <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar(true)" aria-label="Open Navigation">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <div class="mobile-brand">SmartAttend &middot; Console</div>
        <form method="POST" action="{{ route('portal.logout') }}" style="margin:0">
            @csrf
            <button class="btn ghost" type="submit" style="padding:6px 12px;font-size:12px">Logout</button>
        </form>
    </div>

    <!-- MOBILE BACKDROP -->
    <div class="backdrop" id="backdrop" onclick="toggleSidebar(false)"></div>

    <!-- LEFT SIDEBAR NAVIGATION -->
    <aside class="sidebar" id="sidebar">
        <div>
            <!-- Brand -->
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div>
                    <div class="brand-text">SmartAttend</div>
                    <div class="brand-sub">System Web Portal</div>
                </div>
            </div>

            <!-- Profile Info Card -->
            <div class="user-badge-card">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">System Administrator</div>
                </div>
            </div>

            <!-- Quick Broadcast Trigger Button -->
            <button class="sidebar-broadcast-btn" onclick="switchNav('notifications')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <span>New Broadcast</span>
            </button>

            <!-- Navigation Links -->
            <div class="nav-section-title">Management</div>
            <nav class="sidebar-menu" id="sidebarMenu">
                <button class="sidebar-item active" data-mode="orgs" onclick="switchNav('orgs')">
                    <div class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    </div>
                    <span>Organizations</span>
                    <span class="sidebar-badge" id="sidebarPendingBadge" style="display:none">0</span>
                </button>

                <button class="sidebar-item" data-mode="subs" onclick="switchNav('subs')">
                    <div class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    </div>
                    <span>Subscriptions</span>
                </button>

                <button class="sidebar-item" data-mode="packages" onclick="switchNav('packages')">
                    <div class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <span>Packages</span>
                </button>

                <button class="sidebar-item" data-mode="promos" onclick="switchNav('promos')">
                    <div class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <span>Promotions</span>
                </button>

                <button class="sidebar-item" data-mode="notifications" onclick="switchNav('notifications')">
                    <div class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    </div>
                    <span>Push Alerts</span>
                </button>
            </nav>
        </div>

        <!-- Sidebar Sign Out Footer -->
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('portal.logout') }}" style="margin:0">
                @csrf
                <button class="signout-btn" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT PANE -->
    <main class="main-content">
        <div class="content-wrap">
            <!-- ORGANIZATIONS AREA -->
            <div id="orgArea">
                <div class="page-header-row">
                    <div>
                        <h1 class="page-title">Organizations</h1>
                        <div class="page-subtitle">Review new registrations, branch verification &amp; license status</div>
                    </div>
                </div>

                <div class="stats" id="stats"></div>

                <div class="filters" id="filters">
                    <button class="filter active" data-status="pending">Pending Review</button>
                    <button class="filter" data-status="active">Active Organizations</button>
                    <button class="filter" data-status="suspended">Suspended</button>
                    <button class="filter" data-status="">All Organizations</button>
                </div>
                <div id="orgs"></div>
            </div>

            <!-- SUBSCRIPTIONS AREA -->
            <div id="subsArea" style="display:none">
                <div class="page-header-row">
                    <div>
                        <h1 class="page-title">Subscriptions</h1>
                        <div class="page-subtitle">Manage organization billing tiers, trial periods, and limits</div>
                    </div>
                </div>
                <div id="subsContent"></div>
            </div>

            <!-- PACKAGES AREA -->
            <div id="packagesArea" style="display:none">
                <div class="page-header-row">
                    <div>
                        <h1 class="page-title">Subscription Packages</h1>
                        <div class="page-subtitle">Configure public pricing tiers, branch quotas, and features</div>
                    </div>
                </div>
                <div id="packagesContent"></div>
            </div>

            <!-- PROMOS AREA -->
            <div id="promosArea" style="display:none">
                <div class="page-header-row">
                    <div>
                        <h1 class="page-title">Promotions &amp; Vouchers</h1>
                        <div class="page-subtitle">Create discount coupon codes and trial extension vouchers</div>
                    </div>
                </div>
                <div id="promosContent"></div>
            </div>

            <!-- NOTIFICATIONS & BROADCASTS AREA -->
            <div id="notificationsArea" style="display:none">
                <div class="page-header-row">
                    <div>
                        <h1 class="page-title">Push Notifications</h1>
                        <div class="page-subtitle">Broadcast real-time mobile push notifications to all users or specific organizations</div>
                    </div>
                </div>
                <div id="notificationsContent"></div>
            </div>
        </div>
    </main>

    <div class="msg" id="msg"></div>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let mode = 'orgs';
        let current = 'pending';
        const $msg = document.getElementById('msg');
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const cap = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('backdrop');
            sidebar.classList.toggle('open', open);
            backdrop.classList.toggle('open', open);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') toggleSidebar(false);
        });

        function switchNav(targetMode) {
            mode = targetMode;
            document.querySelectorAll('.sidebar-item').forEach(n => {
                n.classList.toggle('active', n.dataset.mode === mode);
            });

            document.getElementById('orgArea').style.display = mode === 'orgs' ? '' : 'none';
            document.getElementById('subsArea').style.display = mode === 'subs' ? '' : 'none';
            document.getElementById('packagesArea').style.display = mode === 'packages' ? '' : 'none';
            document.getElementById('promosArea').style.display = mode === 'promos' ? '' : 'none';
            document.getElementById('notificationsArea').style.display = mode === 'notifications' ? '' : 'none';

            toggleSidebar(false);

            if (mode === 'orgs') loadOrgs().catch(() => {});
            else if (mode === 'subs') loadSubs().catch(() => {});
            else if (mode === 'packages') loadPackages().catch(() => {});
            else if (mode === 'promos') loadPromos().catch(() => {});
            else if (mode === 'notifications') loadNotifications().catch(() => {});
        }

        function setFilterButton() {
            document.querySelectorAll('.filter').forEach(f => {
                f.classList.toggle('active', f.dataset.status === current);
            });
        }

        function toast(text, ok) {
            $msg.textContent = text;
            $msg.className = 'msg ' + (ok ? 'ok' : 'err');
            $msg.style.display = 'block';
            clearTimeout(toast._t);
            toast._t = setTimeout(() => $msg.style.display = 'none', 3500);
        }

        async function api(path, opts = {}) {
            const res = await fetch(path, {
                headers: opts.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } : { 'Accept': 'application/json' },
                ...opts,
            });
            if (res.status === 401 || res.status === 419) { location.href = '/portal'; throw new Error('expired'); }
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(json.message || 'Request failed');
            return json;
        }

        async function loadStats() {
            const { stats } = await api('/portal/api/stats');
            const pendingBadge = document.getElementById('sidebarPendingBadge');
            if (pendingBadge) {
                if (stats.pending > 0) {
                    pendingBadge.textContent = stats.pending;
                    pendingBadge.style.display = 'inline-block';
                } else {
                    pendingBadge.style.display = 'none';
                }
            }

            const tiles = [
                ['Pending Review', stats.pending, 'pending'],
                ['Active Orgs', stats.active, 'active'],
                ['Suspended', stats.suspended, 'suspended'],
                ['Total Orgs', stats.organizations_total, ''],
                ['Employees', stats.employees, null],
                ['Branches', stats.branches, null],
                ['Check-ins today', stats.today_checkins, null],
            ];
            document.getElementById('stats').innerHTML = tiles.map(([label, value, status]) => {
                const inner = `<b>${value}</b><span>${label}</span>`;
                return status === null ? `<div class="stat">${inner}</div>` : `<button class="stat stat-btn" onclick="goOrgFilter(${status === '' ? "''" : `'${status}'`})">${inner}</button>`;
            }).join('');
        }

        function goOrgFilter(status) {
            current = status;
            switchNav('orgs');
            setFilterButton();
            loadOrgs().catch(() => {});
        }

        async function loadOrgs() {
            const q = current ? '?status=' + current : '';
            const { organizations } = await api('/portal/api/organizations' + q);
            const box = document.getElementById('orgs');
            if (!organizations.length) { box.innerHTML = '<p style="color:var(--muted);padding:14px 0">No organizations found in this view.</p>'; return; }
            box.innerHTML = organizations.map(o => {
                const status = { pending: ['Pending review', 'var(--amber)'], active: ['Active', 'var(--green)'], suspended: ['Suspended', 'var(--red)'] }[o.status] || ['Pending', 'var(--amber)'];
                const requestDetails = o.status === 'pending' ? `
                    <div style="margin-top:12px;background:rgba(184,134,11,.06);border:1px solid rgba(184,134,11,.25);border-radius:12px;padding:12px 14px;font-size:13px">
                        <div style="font-weight:800;margin-bottom:6px">Registration Application Details</div>
                        <div><b>Admin:</b> ${esc(o.admin?.name || '—')} &middot; ${esc(o.admin?.email || '—')} &middot; ID ${esc(o.admin?.employee_id || '—')}</div>
                        <div><b>Phone:</b> ${esc(o.admin?.phone || '—')}</div>
                        <div><b>Address:</b> ${esc(o.address || '—')} ${o.website ? '&middot; ' + esc(o.website) : ''}</div>
                        <div><b>TIN / Reg:</b> ${esc(o.tin || '—')} &middot; <b>Submitted:</b> ${fmtDateTime(o.created_at)}</div>
                    </div>` : '';
                const buttons = o.status === 'pending'
                    ? `<button class="btn primary" onclick="approve(${o.id})">Approve &amp; Start Trial</button><button class="btn danger" onclick="act(${o.id},'reject')">Reject</button>`
                    : o.status === 'active'
                        ? `<button class="btn danger" onclick="act(${o.id},'suspend')">Suspend</button>`
                        : `<button class="btn primary" onclick="act(${o.id},'reactivate')">Reactivate</button>`;
                const view = `<button class="btn ghost" onclick="openOrg(${o.id})">View full details</button>`;
                const trial = o.on_trial ? `<div style="color:var(--amber);font-size:12px;font-weight:700;margin-top:8px">Trial ends ${fmtDate(o.trial_ends_at)} &middot; ${o.trial_days_left}d left</div>` : '';
                return `<div class="org">
                    <h3>${esc(o.name)}</h3>
                    <div class="contact">${esc(o.contact_email || o.contact_phone || '—')}</div>
                    ${requestDetails}
                    <div class="meta">
                        <span><span class="pill" style="color:${status[1]};background:${status[1]}1A">${status[0]}</span></span>
                        <span>Plan: <b>${cap(o.plan)}</b></span>
                        <span>Employees: <b>${o.employees_count}/${o.employees_limit === null ? '∞' : o.employees_limit}</b></span>
                        <span>Branches: <b>${o.branches_count}/${o.branches_limit === null ? '∞' : o.branches_limit}</b></span>
                        <span>Prefix: <b>${esc(o.employee_id_prefix || '—')}</b></span>
                    </div>
                    ${trial}
                    <div class="actions">${view}${buttons}</div>
                </div>`;
            }).join('');
        }

        function fmtDate(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function fmtDateTime(iso) {
            if (!iso) return '—';
            return new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        async function approve(id) {
            const raw = prompt('Free trial length in days for this organization?', '30');
            if (raw === null) return;
            const days = parseInt(raw, 10);
            if (!days || days < 1 || days > 365) { toast('Enter a trial length between 1 and 365 days.', false); return; }
            if (!confirm('Approve this organization and start a ' + days + '-day free trial?')) return;
            try {
                const json = await api(`/portal/api/organizations/${id}/approve`, { method: 'POST', body: JSON.stringify({ days }) });
                toast(json.message || 'Organization approved', true);
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) { toast(e.message, false); }
        }

        async function act(id, type) {
            const names = {
                reject: ['Reject and suspend this registration?', () => api(`/portal/api/organizations/${id}/status`, { method: 'POST', body: JSON.stringify({ status: 'suspended' }) })],
                suspend: ['Suspend this organization? Users will lose access.', () => api(`/portal/api/organizations/${id}/status`, { method: 'POST', body: JSON.stringify({ status: 'suspended' }) })],
                reactivate: ['Reactivate this organization?', () => api(`/portal/api/organizations/${id}/status`, { method: 'POST', body: JSON.stringify({ status: 'active' }) })],
            }[type];
            if (!names || !confirm(names[0])) return;
            try {
                const json = await names[1]();
                toast(json.message || 'Done', true);
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) {
                toast(e.message, false);
            }
        }

        function statusPill(o) {
            const m = { pending: ['Pending review', 'var(--amber)'], active: ['Active', 'var(--green)'], suspended: ['Suspended', 'var(--red)'] }[o.status] || ['Pending', 'var(--amber)'];
            return `<span class="pill" style="color:${m[1]};background:${m[1]}1A">${m[0]}</span>`;
        }

        async function openOrg(id) {
            try {
                const { organization: o } = await api('/portal/api/organizations/' + id);
                renderOrgDetail(o);
            } catch (e) { if (e.message !== 'expired') toast(e.message, false); }
        }

        function renderOrgDetail(o) {
            const actions = o.status === 'pending'
                ? `<button class="btn primary" onclick="approve(${o.id})">Approve &amp; Start Trial</button><button class="btn danger" onclick="act(${o.id},'reject')">Reject</button>`
                : o.status === 'active'
                    ? `<button class="btn danger" onclick="actFromDetail(${o.id},'suspend')">Suspend</button>`
                    : `<button class="btn primary" onclick="actFromDetail(${o.id},'reactivate')">Reactivate</button>`;
            const admin = o.admin;
            const subs = o.subscription_status === 'canceled' ? 'Canceled' : (o.on_trial ? 'Free trial' : (o.subscription_status === 'active' ? 'Active' : '—'));
            document.getElementById('orgs').innerHTML = `
                <button class="btn ghost" onclick="loadOrgs()">&larr; Back to organizations</button>
                <div class="org" style="margin-top:14px">
                    <h3>${esc(o.name)} ${statusPill(o)}</h3>
                    <div class="contact">Registered ${fmtDateTime(o.created_at)}</div>

                    <h4 style="margin:18px 0 8px">Organization Profile</h4>
                    <div class="grid">
                        ${kv('Contact email', o.contact_email)}${kv('Phone', o.contact_phone)}${kv('Address', o.address)}${kv('Website', o.website)}${kv('TIN / Reg', o.tin)}${kv('ID prefix', o.employee_id_prefix)}
                    </div>

                    <h4 style="margin:18px 0 8px">Admin / Applicant</h4>
                    <div class="grid">
                        ${admin ? `${kv('Name', admin.name)}${kv('Email', admin.email)}${kv('Phone', admin.phone)}${kv('Employee ID', admin.employee_id)}` : kv('Admin', '—')}
                    </div>

                    <h4 style="margin:18px 0 8px">Plan &amp; Subscription</h4>
                    <div class="grid">
                        ${kv('Plan', cap(o.plan))}${kv('Subscription', subs)}${kv('Trial ends', fmtDate(o.trial_ends_at))}${kv('Trial days left', o.on_trial ? String(o.trial_days_left) : '—')}
                        ${kv('Employees', o.employees_count + ' / ' + (o.employees_limit === null ? 'unlimited' : o.employees_limit))}${kv('Branches', o.branches_count + ' / ' + (o.branches_limit === null ? 'unlimited' : o.branches_limit))}
                    </div>

                    <h4 style="margin:18px 0 8px">Branches (${o.branches.length})</h4>
                    ${o.branches.length === 0 ? '<div class="contact">No branches registered</div>' : o.branches.map(b => `<div class="branch">${esc(b.name)} <span>${b.lat}, ${b.lng} &middot; &plusmn;${b.radius_meters} m &middot; ${b.employee_count} employee${b.employee_count === 1 ? '' : 's'}</span></div>`).join('')}

                    <h4 style="margin:18px 0 8px">Employees (${o.employees.length})</h4>
                    ${o.employees.length === 0 ? '<div class="contact">No employees enrolled yet</div>' : `<table class="emps">
                        <thead><tr><th>Name</th><th>ID</th><th>Branch</th><th>Face Enrolled</th><th>Status</th></tr></thead>
                        <tbody>${o.employees.map(e => `<tr><td>${esc(e.name)}</td><td>${esc(e.employee_id)}</td><td>${esc(e.branch)}</td><td>${e.face_enrolled ? 'Yes' : 'No'}</td><td>${e.active ? 'Active' : 'Inactive'}</td></tr>`).join('')}</tbody>
                    </table>`}
                    <div class="actions">${actions}</div>
                </div>`;
        }

        async function actFromDetail(id, type) {
            await act(id, type);
        }

        function kv(k, v) {
            return `<div><span>${esc(k)}</span><b>${esc(v == null || v === '' ? '—' : v)}</b></div>`;
        }

        async function loadSubs() {
            const { organizations } = await api('/portal/api/organizations');
            const box = document.getElementById('subsContent');
            const list = organizations.filter(o => o.status !== 'pending');
            if (!list.length) { box.innerHTML = '<p style="color:var(--muted);padding:14px 0">No active subscriptions yet.</p>'; return; }
            box.innerHTML = list.map(o => {
                const sub = o.subscription_status === 'canceled' ? 'Canceled'
                    : o.on_trial ? 'Free trial'
                    : o.subscription_status === 'active' ? 'Active' : 'No trial';
                const subColor = o.subscription_status === 'canceled' ? 'var(--red)' : (o.subscription_status === 'active' || o.on_trial ? 'var(--green)' : 'var(--amber)');
                const select = `<select id="plan_${o.id}" style="font-weight:700">${['starter','business','enterprise'].map(p => `<option value="${p}" ${p === o.plan ? 'selected' : ''}>${cap(p)}</option>`).join('')}</select>`;
                const manage = o.status === 'suspended'
                    ? '<div style="color:var(--red);font-size:12px;font-weight:700">Organization suspended</div>'
                    : `<button class="btn ghost" onclick="subAction(${o.id},'set_plan')">Set plan</button>
                       <button class="btn ghost" onclick="extendTrial(${o.id})">Extend trial</button>
                       ${o.subscription_status === 'canceled'
                            ? `<button class="btn primary" onclick="subAction(${o.id},'reactivate')">Reactivate</button>`
                            : `<button class="btn danger" onclick="subAction(${o.id},'cancel')">Cancel</button>`}`;
                return `<div class="org">
                    <h3>${esc(o.name)} <span class="pill" style="color:${subColor};background:${subColor}1A">${sub}</span></h3>
                    <div class="meta">
                        <span>Plan: <b>${cap(o.plan)}</b></span>
                        <span>Trial ends: <b>${fmtDate(o.trial_ends_at)}</b></span>
                        <span>${o.on_trial ? o.trial_days_left + 'd left' : ''}</span>
                        <span>Employees: <b>${o.employees_count}/${o.employees_limit === null ? '∞' : o.employees_limit}</b></span>
                        <span>Branches: <b>${o.branches_count}/${o.branches_limit === null ? '∞' : o.branches_limit}</b></span>
                    </div>
                    <div class="actions">${select}${manage}</div>
                </div>`;
            }).join('');
        }

        async function extendTrial(id) {
            const raw = prompt('Add how many trial days?', '30');
            if (raw === null) return;
            const days = parseInt(raw, 10);
            if (!days || days < 1 || days > 365) { toast('Enter between 1 and 365 days.', false); return; }
            await subAction(id, 'extend_trial', { days });
        }

        async function subAction(id, action, extra = {}) {
            try {
                const body = { action, ...extra };
                if (action === 'set_plan') body.plan = document.getElementById('plan_' + id).value;
                const json = await api(`/portal/api/organizations/${id}/subscription`, { method: 'POST', body: JSON.stringify(body) });
                toast(json.message || 'Updated', true);
                await loadSubs();
            } catch (e) { toast(e.message, false); }
        }

        async function loadPackages() {
            const { packages } = await api('/portal/api/packages');
            const box = document.getElementById('packagesContent');
            box.innerHTML = packages.map(p => `
                <div class="org">
                    <h3>${esc(p.name)} <span style="color:var(--muted);font-weight:600;font-size:12px">${esc(p.code)}</span></h3>
                    <label class="plabel">Package name</label>
                    <input class="pfield" id="pk_name_${p.id}" value="${esc(p.name)}">
                    <label class="plabel">Tagline</label>
                    <input class="pfield" id="pk_tag_${p.id}" value="${esc(p.tagline || '')}">
                    <label class="plabel">Price label (e.g. TZS 50,000/mo)</label>
                    <input class="pfield" id="pk_price_${p.id}" value="${esc(p.price_label || '')}">
                    <label class="plabel">Employee limit (blank = unlimited)</label>
                    <input class="pfield" id="pk_emp_${p.id}" type="number" min="1" value="${p.employee_limit == null ? '' : p.employee_limit}">
                    <label class="plabel">Branch limit (blank = unlimited)</label>
                    <input class="pfield" id="pk_br_${p.id}" type="number" min="1" value="${p.branch_limit == null ? '' : p.branch_limit}">
                    <label class="plabel">Features (one per line)</label>
                    <textarea class="pfield" id="pk_feat_${p.id}" rows="5">${esc((p.features || []).join('\n'))}</textarea>
                    <label class="switch"><input type="checkbox" id="pk_active_${p.id}" ${p.active ? 'checked' : ''}> Active (offered in registration &amp; upgrades)</label>
                    <div class="actions" style="margin-top:12px">
                        <button class="btn primary" onclick="savePackage(${p.id})">Save Package</button>
                    </div>
                </div>`).join('');
        }

        async function savePackage(id) {
            const val = (x) => document.getElementById(x)?.value.trim();
            const featRaw = val('pk_feat_' + id);
            const empRaw = val('pk_emp_' + id);
            const brRaw = val('pk_br_' + id);
            const body = {
                name: val('pk_name_' + id) || document.getElementById('pk_name_' + id).value,
                tagline: val('pk_tag_' + id) || null,
                price_label: val('pk_price_' + id) || null,
                features: featRaw ? featRaw.split('\n').map(s => s.trim()).filter(Boolean) : [],
                employee_limit: empRaw ? parseInt(empRaw, 10) : null,
                branch_limit: brRaw ? parseInt(brRaw, 10) : null,
                active: document.getElementById('pk_active_' + id).checked,
                position: 0,
            };
            try {
                const json = await api('/portal/api/packages/' + id, { method: 'PUT', body: JSON.stringify(body) });
                toast(json.message || 'Package saved', true);
                await loadPackages();
            } catch (e) { toast(e.message, false); }
        }

        async function loadPromos() {
            const { promos } = await api('/portal/api/promos');
            const box = document.getElementById('promosContent');
            const newCard = `<div class="org">
                <h3>Create New Promo Code</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div><label class="plabel">Code (e.g. OFFER20)</label><input class="pfield" id="np_code"></div>
                    <div><label class="plabel">Type</label><select class="pfield" id="np_type"><option value="trial_days">Extra trial days</option><option value="discount_percent">% discount</option></select></div>
                </div>
                <label class="plabel">Title</label><input class="pfield" id="np_title">
                <label class="plabel">Value</label><input class="pfield" id="np_value" type="number" min="1" value="10">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div><label class="plabel">Max uses (blank = unlimited)</label><input class="pfield" id="np_max" type="number" min="1"></div>
                    <div><label class="plabel">Ends (yyyy-mm-dd, optional)</label><input class="pfield" id="np_ends"></div>
                </div>
                <div class="actions"><button class="btn primary" onclick="createPromo()">Create Promo</button></div>
            </div>`;
            const list = promos.map(p => {
                const usage = (p.max_uses == null ? p.redeemed + ' used' : p.redeemed + ' / ' + p.max_uses);
                return `<div class="org">
                    <h3>${esc(p.code)} <span class="pill" style="color:${p.active ? 'var(--green)' : 'var(--red)'};background:${p.active ? 'var(--green)' : 'var(--red)'}1A">${p.active ? 'Active' : 'Inactive'}</span></h3>
                    <div class="contact">${esc(p.label)} &middot; ${usage} &middot; ${p.ends_at ? 'ends ' + fmtDate(p.ends_at) : 'no end'}</div>
                    <label class="plabel">Title</label><input class="pfield" id="pm_title_${p.id}" value="${esc(p.title)}">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                        <div><label class="plabel">Type</label><select class="pfield" id="pm_type_${p.id}"><option value="trial_days" ${p.type === 'trial_days' ? 'selected' : ''}>Extra trial days</option><option value="discount_percent" ${p.type === 'discount_percent' ? 'selected' : ''}>% discount</option></select></div>
                        <div><label class="plabel">Value</label><input class="pfield" id="pm_value_${p.id}" type="number" min="1" value="${p.value}"></div>
                        <div><label class="plabel">Max uses</label><input class="pfield" id="pm_max_${p.id}" type="number" min="1" value="${p.max_uses == null ? '' : p.max_uses}"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div><label class="plabel">Ends (yyyy-mm-dd)</label><input class="pfield" id="pm_ends_${p.id}" value="${p.ends_at ? String(p.ends_at).slice(0,10) : ''}"></div>
                        <div><label class="plabel">Description</label><input class="pfield" id="pm_desc_${p.id}" value="${esc(p.description || '')}"></div>
                    </div>
                    <label class="switch"><input type="checkbox" id="pm_active_${p.id}" ${p.active ? 'checked' : ''}> Active</label>
                    <div class="actions"><button class="btn primary" onclick="updatePromo(${p.id})">Save</button></div>
                </div>`;
            }).join('');
            box.innerHTML = newCard + list;
        }

        async function createPromo() {
            const body = {
                code: document.getElementById('np_code').value.trim().toUpperCase(),
                title: document.getElementById('np_title').value.trim(),
                type: document.getElementById('np_type').value,
                value: parseInt(document.getElementById('np_value').value, 10),
                max_uses: document.getElementById('np_max').value ? parseInt(document.getElementById('np_max').value, 10) : null,
                ends_at: document.getElementById('np_ends').value || null,
                active: true,
            };
            if (!body.code || !body.title || !body.value) { toast('Fill in code, title and value.', false); return; }
            try {
                const json = await api('/portal/api/promos', { method: 'POST', body: JSON.stringify(body) });
                toast(json.message || 'Promo created', true);
                await loadPromos();
            } catch (e) { toast(e.message, false); }
        }

        async function updatePromo(id) {
            const body = {
                title: document.getElementById('pm_title_' + id).value.trim(),
                description: document.getElementById('pm_desc_' + id).value.trim() || null,
                type: document.getElementById('pm_type_' + id).value,
                value: parseInt(document.getElementById('pm_value_' + id).value, 10),
                max_uses: document.getElementById('pm_max_' + id).value ? parseInt(document.getElementById('pm_max_' + id).value, 10) : null,
                ends_at: document.getElementById('pm_ends_' + id).value || null,
                active: document.getElementById('pm_active_' + id).checked,
            };
            try {
                const json = await api('/portal/api/promos/' + id, { method: 'PUT', body: JSON.stringify(body) });
                toast(json.message || 'Promo saved', true);
                await loadPromos();
            } catch (e) { toast(e.message, false); }
        }

        async function loadNotifications() {
            const data = await api('/portal/api/notifications/history');
            const box = document.getElementById('notificationsContent');
            const stats = data.stats || {};
            const orgs = data.organizations || [];
            const broadcasts = data.recent_broadcasts || [];

            const statsHtml = `
                <div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
                    <div class="stat"><b>${stats.total_devices || 0}</b><span>Active Push Devices</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">${stats.android_devices || 0} Android &middot; ${stats.ios_devices || 0} iOS</small></div>
                    <div class="stat"><b>${stats.total_users || 0}</b><span>Total App Users</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">Eligible recipients</small></div>
                    <div class="stat"><b>${stats.broadcasts_count || 0}</b><span>Broadcasts Sent</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">All-time dispatched</small></div>
                </div>`;

            const orgOptions = orgs.map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');

            const composerHtml = `
                <div class="org" style="margin-bottom:20px">
                    <h3 style="display:flex;align-items:center;gap:8px">📣 Dispatch Real-Time Push Notification</h3>
                    <p style="color:var(--muted);font-size:13px;margin:4px 0 14px">Broadcast instant push alerts with sound and heads-up banner to app users.</p>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label class="plabel">Target Audience</label>
                            <select class="pfield" id="nb_audience" onchange="toggleNotifOrgSelect(this.value)">
                                <option value="all">All Active Users</option>
                                <option value="org_admin">Organization Admins Only</option>
                                <option value="employee">Employees Only</option>
                                <option value="org">Specific Organization</option>
                            </select>
                        </div>
                        <div id="nb_org_wrap" style="display:none">
                            <label class="plabel">Target Organization</label>
                            <select class="pfield" id="nb_org_id">
                                ${orgOptions}
                            </select>
                        </div>
                    </div>

                    <label class="plabel">Notification Title</label>
                    <input class="pfield" id="nb_title" maxlength="100" placeholder="e.g. System Maintenance Notice">

                    <label class="plabel">Message Body</label>
                    <textarea class="pfield" id="nb_body" rows="3" maxlength="500" placeholder="Enter announcement text to display on mobile devices..."></textarea>

                    <div class="actions" style="margin-top:14px">
                        <button class="btn primary" id="nb_submit_btn" onclick="sendBroadcast()">Dispatch Push Notification</button>
                    </div>
                </div>`;

            const historyRows = broadcasts.length === 0
                ? '<p style="color:var(--muted);margin-top:10px">No push broadcasts dispatched yet.</p>'
                : `<table class="emps" style="margin-top:12px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--border)">
                    <thead>
                        <tr>
                            <th>Title &amp; Message</th>
                            <th>Target Audience</th>
                            <th>Dispatched By</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${broadcasts.map(b => `
                            <tr>
                                <td style="max-width:320px">
                                    <div style="font-weight:800;color:var(--ink)">${esc(b.title)}</div>
                                    <div style="color:var(--muted);font-size:12px;margin-top:2px">${esc(b.body)}</div>
                                </td>
                                <td><span class="pill" style="color:var(--ink);background:rgba(32,15,53,.07)">${esc(b.organization?.name || 'System Wide')}</span></td>
                                <td style="color:var(--muted)">${esc(b.sender?.name || 'Admin')}</td>
                                <td style="color:var(--muted);white-space:nowrap">${fmtDateTime(b.created_at)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>`;

            const historyHtml = `
                <div style="margin-top:16px">
                    <h3 style="font-size:16px;margin-bottom:8px">Dispatched Broadcast Logs</h3>
                    ${historyRows}
                </div>`;

            box.innerHTML = statsHtml + composerHtml + historyHtml;
        }

        function toggleNotifOrgSelect(audience) {
            const wrap = document.getElementById('nb_org_wrap');
            if (wrap) wrap.style.display = audience === 'org' ? '' : 'none';
        }

        async function sendBroadcast() {
            const title = document.getElementById('nb_title')?.value.trim();
            const body = document.getElementById('nb_body')?.value.trim();
            const audience = document.getElementById('nb_audience')?.value || 'all';
            const orgId = audience === 'org' ? document.getElementById('nb_org_id')?.value : null;

            if (!title) { toast('Please enter a notification title.', false); return; }
            if (!body) { toast('Please enter a message body.', false); return; }

            if (!confirm(`Dispatch this push notification to ${audience === 'all' ? 'all users' : audience}?`)) return;

            const btn = document.getElementById('nb_submit_btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Dispatching...'; }

            try {
                const payload = {
                    title,
                    body,
                    org_id: orgId ? parseInt(orgId, 10) : undefined,
                    role: ['all', 'org'].includes(audience) ? undefined : audience,
                };
                const json = await api('/portal/api/notifications/broadcast', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                toast(json.message || 'Push broadcast sent successfully!', true);
                await loadNotifications();
            } catch (e) {
                toast(e.message || 'Failed to dispatch notification.', false);
                if (btn) { btn.disabled = false; btn.textContent = 'Dispatch Push Notification'; }
            }
        }

        document.getElementById('filters').addEventListener('click', (e) => {
            const btn = e.target.closest('.filter');
            if (!btn) return;
            current = btn.dataset.status;
            setFilterButton();
            loadOrgs().catch((e) => e.message !== 'expired' && toast(e.message, false));
        });

        (async () => {
            setFilterButton();
            try {
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) { /* redirect handled */ }
        })();
    </script>
</body>
</html>
