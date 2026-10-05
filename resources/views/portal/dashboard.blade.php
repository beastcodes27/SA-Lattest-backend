<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartAttend Admin &middot; Dashboard</title>
    <style>
        :root {
            --bg: #D7FFE0;
            --ink: #200F35;
            --sidebar-bg: #1B0B2E;
            --sidebar-hover: rgba(215, 255, 224, 0.08);
            --sidebar-active: #2F174B;
            --text: #1A1231;
            --muted: #55576B;
            --muted-light: #8A86A3;
            --green: #1F7A43;
            --amber: #B8860B;
            --red: #C23030;
            --card-border: rgba(32, 15, 53, 0.1);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; overflow-x: hidden; }
        
        .app-layout { display: flex; min-height: 100vh; width: 100vw; position: relative; }
        
        /* Sidebar container */
        .app-sidebar {
            width: 270px;
            background: var(--sidebar-bg);
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.18);
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .brand-logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #38EF7D 0%, #11998E 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1A1231;
            font-weight: 900;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(56, 239, 125, 0.3);
        }
        .brand-text { display: flex; flex-direction: column; }
        .brand-title { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.2px; }
        .brand-badge { font-size: 11px; font-weight: 700; color: #38EF7D; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .sidebar-profile {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0, 0, 0, 0.18);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 20px;
            background: #3F1D6B;
            color: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            border: 2px solid rgba(56, 239, 125, 0.4);
        }
        .profile-info { flex: 1; overflow: hidden; }
        .profile-name { font-size: 14px; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .profile-role { font-size: 11px; color: var(--muted-light); display: flex; align-items: center; gap: 5px; margin-top: 1px; }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: #38EF7D; box-shadow: 0 0 8px #38EF7D; display: inline-block; }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .nav-section-title {
            font-size: 11px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 12px 12px 6px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 11px 14px;
            background: transparent;
            border: 0;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            text-align: left;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .nav-item:hover {
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(3px);
        }
        .nav-item:focus-visible {
            outline: 2px solid #38EF7D;
            outline-offset: 1px;
        }
        .nav-item.active {
            background: var(--sidebar-active);
            color: #38EF7D;
            font-weight: 800;
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: #38EF7D;
            box-shadow: 0 0 10px #38EF7D;
        }
        .nav-icon {
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: inherit;
        }
        .nav-label { flex: 1; }
        .nav-badge {
            background: var(--red);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(194, 48, 48, 0.4);
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.05); }
        }

        .sidebar-quick-stats {
            background: rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 6px 0 10px;
        }
        .sq-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
        }
        .sq-label { color: rgba(255, 255, 255, 0.6); font-weight: 600; }
        .sq-value { color: #38EF7D; font-weight: 800; }

        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.15);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            background: rgba(194, 48, 48, 0.18);
            border: 1px solid rgba(194, 48, 48, 0.35);
            border-radius: 10px;
            color: #FF8E8E;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .logout-btn:hover {
            background: rgba(194, 48, 48, 0.3);
            color: #fff;
        }

        /* Main view area */
        .app-main {
            flex: 1;
            margin-left: 270px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: calc(100vw - 270px);
        }
        
        .main-topbar {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(32, 15, 53, 0.08);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .menu-toggle-btn {
            display: none;
            background: rgba(32, 15, 53, 0.06);
            border: 0;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            color: var(--ink);
            font-size: 16px;
        }
        .page-title { font-size: 18px; font-weight: 800; color: var(--ink); margin: 0; }
        .page-breadcrumb { font-size: 12px; color: var(--muted); font-weight: 600; margin-top: 1px; }

        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .live-clock {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            display: none;
        }
        @media (min-width: 1200px) {
            .live-clock { display: block; }
        }
        .topbar-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--ink);
            color: var(--bg);
            border: 0;
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 12.5px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .topbar-action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .topbar-action-btn.ghost {
            background: rgba(32, 15, 53, 0.06);
            color: var(--ink);
        }
        .topbar-action-btn.ghost:hover {
            background: rgba(32, 15, 53, 0.12);
        }
        .system-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(31, 122, 67, 0.1);
            color: var(--green);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .main-content {
            flex: 1;
            padding: 24px 28px 60px;
            max-width: 1400px;
            width: 100%;
        }

        /* Sidebar backdrop for mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 95;
            backdrop-filter: blur(2px);
        }

        /* Cards, Stats & Forms */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat { background: #fff; border: 1px solid var(--card-border); border-radius: 14px; padding: 16px; box-shadow: 0 2px 8px rgba(32, 15, 53, 0.03); }
        .stat b { font-size: 26px; color: var(--ink); display: block; letter-spacing: -0.5px; }
        .stat span { color: var(--muted); font-size: 12px; font-weight: 600; margin-top: 2px; display: block; }
        .stat-btn { display: block; width: 100%; text-align: left; font: inherit; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; }
        .stat-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(32, 15, 53, 0.08); }
        
        .filters { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
        .filter { border: 1px solid rgba(32, 15, 53, 0.18); background: #fff; border-radius: 999px; padding: 8px 18px; cursor: pointer; font-weight: 700; font-size: 13px; color: var(--text); transition: all 0.15s; }
        .filter:hover { background: rgba(32, 15, 53, 0.04); }
        .filter.active { background: var(--ink); color: var(--bg); border-color: var(--ink); }
        
        .org { background: #fff; border: 1px solid var(--card-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(32, 15, 53, 0.03); }
        .org h3 { margin: 0 0 3px; font-size: 17px; color: var(--ink); }
        .org .contact { color: var(--muted); font-size: 13px; margin-bottom: 12px; }
        .meta { display: flex; gap: 14px; font-size: 12px; color: var(--muted); flex-wrap: wrap; align-items: center; }
        .pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 800; }
        
        .actions { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
        .btn { border: 0; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 800; cursor: pointer; transition: all 0.15s; }
        .btn.primary { background: var(--ink); color: var(--bg); }
        .btn.primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn.danger { background: rgba(194, 48, 48, 0.1); color: var(--red); }
        .btn.danger:hover { background: rgba(194, 48, 48, 0.2); }
        .btn.ghost { background: rgba(32, 15, 53, 0.06); color: var(--ink); }
        .btn.ghost:hover { background: rgba(32, 15, 53, 0.12); }
        .btn[disabled] { opacity: 0.5; cursor: default; }

        .msg { position: fixed; left: 50%; transform: translateX(-50%); top: 18px; border-radius: 12px; padding: 12px 22px; font-weight: 700; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.25); display: none; z-index: 1000; }
        .msg.ok { background: var(--green); }
        .msg.err { background: var(--red); }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .grid span { display: block; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        .grid b { font-size: 13.5px; word-break: break-word; color: var(--ink); margin-top: 2px; }
        .branch { background: rgba(32, 15, 53, 0.04); border: 1px solid rgba(32, 15, 53, 0.1); border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 800; margin-bottom: 8px; }
        .branch span { font-weight: 400; color: var(--muted); font-size: 12px; }
        table.emps { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.emps th { text-align: left; background: rgba(32, 15, 53, 0.06); padding: 9px 12px; color: var(--ink); font-size: 11px; text-transform: uppercase; font-weight: 800; }
        table.emps td { padding: 9px 12px; border-bottom: 1px solid rgba(32, 15, 53, 0.08); }
        
        select { padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(32, 15, 53, 0.2); background: #fff; font-weight: 600; }
        .pfield { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(32, 15, 53, 0.2); font-size: 13.5px; margin-top: 5px; background: #fff; color: var(--text); }
        .pfield:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px rgba(32, 15, 53, 0.1); }
        .plabel { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ink); margin-top: 12px; }
        .switch { display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; cursor: pointer; margin-top: 12px; }

        @media (max-width: 992px) {
            .app-sidebar {
                transform: translateX(-100%);
            }
            .app-sidebar.open {
                transform: translateX(0);
            }
            .sidebar-backdrop.open {
                display: block;
            }
            .app-main {
                margin-left: 0;
                width: 100vw;
            }
            .menu-toggle-btn {
                display: inline-flex;
            }
            .main-content {
                padding: 18px 16px 40px;
            }
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <!-- Mobile Drawer Backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar(false)"></div>

        <!-- System Admin Sidebar Navigation -->
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo-icon">SA</div>
                <div class="brand-text">
                    <div class="brand-title">SmartAttend</div>
                    <div class="brand-badge">System Console</div>
                </div>
            </div>

            <div class="sidebar-profile">
                <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'SA', 0, 2)) }}</div>
                <div class="profile-info">
                    <div class="profile-name">{{ auth()->user()->name }}</div>
                    <div class="profile-role">
                        <span class="status-dot"></span> System Super Admin
                    </div>
                </div>
            </div>

            <div class="sidebar-nav" id="sidebarNav">
                <div class="nav-section-title">Administration</div>
                <button class="nav-item active" data-mode="orgs">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M10 11h2M10 15h2M14 11h2M14 15h2M9 21V3h6v18"/></svg>
                    </span>
                    <span class="nav-label">Organizations</span>
                    <span class="nav-badge" id="pendingBadge" style="display:none">0</span>
                </button>
                <button class="nav-item" data-mode="subs">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    </span>
                    <span class="nav-label">Subscriptions</span>
                </button>
                <button class="nav-item" data-mode="packages">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                    </span>
                    <span class="nav-label">Package Tiers</span>
                </button>
                <button class="nav-item" data-mode="promos">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    </span>
                    <span class="nav-label">Promos &amp; Discounts</span>
                </button>
                <button class="nav-item" data-mode="notifications">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                    </span>
                    <span class="nav-label">Push Broadcasts</span>
                </button>

                <div class="nav-section-title" style="margin-top:14px">Live Summary</div>
                <div class="sidebar-quick-stats">
                    <div class="sq-stat">
                        <span class="sq-label">Active Orgs</span>
                        <span class="sq-value" id="sqActiveOrgs">—</span>
                    </div>
                    <div class="sq-stat">
                        <span class="sq-label">Total Staff</span>
                        <span class="sq-value" id="sqTotalStaff">—</span>
                    </div>
                    <div class="sq-stat">
                        <span class="sq-label">Today's Checkins</span>
                        <span class="sq-value" id="sqTodayCheckins">—</span>
                    </div>
                </div>
            </div>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('portal.logout') }}" onsubmit="return confirm('Are you sure you want to sign out of the System Admin Console?');">
                    @csrf
                    <button class="logout-btn" type="submit">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Sign out of Console</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="app-main">
            <header class="main-topbar">
                <div class="topbar-left">
                    <button class="menu-toggle-btn" onclick="toggleSidebar(true)">☰</button>
                    <div>
                        <h1 class="page-title" id="pageTitle">Organizations</h1>
                        <div class="page-breadcrumb" id="pageBreadcrumb">Console &middot; Organizations &amp; Registrations</div>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="live-clock" id="liveClock"></div>
                    <button class="topbar-action-btn ghost" onclick="syncAllData()" title="Refresh live statistics">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        <span>Refresh</span>
                    </button>
                    <button class="topbar-action-btn" onclick="quickBroadcast()" title="Dispatch instant push announcement">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                        <span>Broadcast Alert</span>
                    </button>
                    <span class="system-pill"><span class="status-dot"></span> System Live</span>
                </div>
            </header>

            <main class="main-content">
                <div id="orgArea">
                    <div class="stats" id="stats"></div>
                    <div class="filters" id="filters">
                        <button class="filter active" data-status="pending">Pending</button>
                        <button class="filter" data-status="active">Active</button>
                        <button class="filter" data-status="suspended">Suspended</button>
                        <button class="filter" data-status="">All</button>
                    </div>
                    <div id="orgs"></div>
                </div>

                <div id="subsArea" style="display:none"></div>

                <div id="packagesArea" style="display:none"></div>

                <div id="promosArea" style="display:none"></div>

                <div id="notificationsArea" style="display:none"></div>
            </main>
        </div>
    </div>

    <div class="msg" id="msg"></div>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let current = 'pending';
        const $msg = document.getElementById('msg');
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const cap = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

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
            if (stats.pending !== undefined) {
                const badge = document.getElementById('pendingBadge');
                if (badge) {
                    badge.textContent = stats.pending;
                    badge.style.display = stats.pending > 0 ? 'inline-block' : 'none';
                }
            }
            if (document.getElementById('sqActiveOrgs')) {
                document.getElementById('sqActiveOrgs').textContent = stats.active ?? '0';
            }
            if (document.getElementById('sqTotalStaff')) {
                document.getElementById('sqTotalStaff').textContent = stats.employees ?? '0';
            }
            if (document.getElementById('sqTodayCheckins')) {
                document.getElementById('sqTodayCheckins').textContent = stats.today_checkins ?? '0';
            }
            const tiles = [
                ['Pending', stats.pending, 'pending'], ['Active', stats.active, 'active'], ['Suspended', stats.suspended, 'suspended'],
                ['Organizations', stats.organizations_total, ''], ['Employees', stats.employees, null], ['Branches', stats.branches, null], ['Check-ins today', stats.today_checkins, null],
            ];
            document.getElementById('stats').innerHTML = tiles.map(([label, value, status]) => {
                const inner = `<b>${value}</b><span>${label}</span>`;
                return status === null ? `<div class="stat">${inner}</div>` : `<button class="stat stat-btn" onclick="goOrgFilter(${status === '' ? "''" : `'${status}'`})">${inner}</button>`;
            }).join('');
        }

        function goOrgFilter(status) {
            current = status;
            setFilterButton();
            switchTab('orgs', true);
        }

        async function loadOrgs() {
            const q = current ? '?status=' + current : '';
            const { organizations } = await api('/portal/api/organizations' + q);
            const box = document.getElementById('orgs');
            if (!organizations.length) { box.innerHTML = '<p style="color:var(--muted)">No organizations in this view.</p>'; return; }
            box.innerHTML = organizations.map(o => {
                const status = { pending: ['Pending review', 'var(--amber)'], active: ['Active', 'var(--green)'], suspended: ['Suspended', 'var(--red)'] }[o.status] || ['Pending', 'var(--amber)'];
                const requestDetails = o.status === 'pending' ? `
                    <div style="margin-top:12px;background:rgba(184,134,11,.06);border:1px solid rgba(184,134,11,.25);border-radius:12px;padding:12px 14px;font-size:13px">
                        <div style="font-weight:800;margin-bottom:6px">Registration request</div>
                        <div><b>Admin:</b> ${esc(o.admin?.name || '—')} &middot; ${esc(o.admin?.email || '—')} &middot; ID ${esc(o.admin?.employee_id || '—')}</div>
                        <div><b>Phone:</b> ${esc(o.admin?.phone || '—')}</div>
                        <div><b>Address:</b> ${esc(o.address || '—')} ${o.website ? '&middot; ' + esc(o.website) : ''}</div>
                        <div><b>TIN / Reg:</b> ${esc(o.tin || '—')} &middot; <b>Requested:</b> ${fmtDateTime(o.created_at)}</div>
                    </div>` : '';
                const buttons = o.status === 'pending'
                    ? `<button class="btn primary" onclick="approve(${o.id})">Approve &amp; Start Trial</button><button class="btn danger" onclick="act(${o.id},'reject')">Reject</button>`
                    : o.status === 'active'
                        ? `<button class="btn danger" onclick="act(${o.id},'suspend')">Suspend</button>`
                        : `<button class="btn primary" onclick="act(${o.id},'reactivate')">Reactivate</button>`;
                const view = `<button class="btn ghost" onclick="openOrg(${o.id})">View details</button>`;
                const trial = o.on_trial ? `<div style="color:var(--amber);font-size:12px;font-weight:600;margin-top:8px">Trial ends ${fmtDate(o.trial_ends_at)} &middot; ${o.trial_days_left}d left</div>` : '';
                return `<div class="org">
                    <h3>${esc(o.name)}</h3>
                    <div class="contact">${esc(o.contact_email || o.contact_phone || '—')}</div>
                    ${requestDetails}
                    <div class="meta">
                        <span><span class="pill" style="color:${status[1]};background:${status[1]}1A">${status[0]}</span></span>
                        <span>Plan: <b>${cap(o.plan)}</b></span>
                        <span>Employees ${o.employees_count}/${o.employees_limit === null ? '∞' : o.employees_limit}</span>
                        <span>Branches ${o.branches_count}/${o.branches_limit === null ? '∞' : o.branches_limit}</span>
                        <span>Prefix ${esc(o.employee_id_prefix || '—')}</span>
                    </div>
                    ${trial}
                    <div class="actions">${view}${buttons}</div>
                </div>`;
            }).join('');
        }

        function fmtDate(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
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

                    <h4 style="margin:18px 0 8px">Organization</h4>
                    <div class="grid">
                        ${kv('Contact email', o.contact_email)}${kv('Phone', o.contact_phone)}${kv('Address', o.address)}${kv('Website', o.website)}${kv('TIN / Reg', o.tin)}${kv('ID prefix', o.employee_id_prefix)}
                    </div>

                    <h4 style="margin:18px 0 8px">Admin / Applicant</h4>
                    <div class="grid">
                        ${admin ? `${kv('Name', admin.name)}${kv('Email', admin.email)}${kv('Phone', admin.phone)}${kv('Employee ID', admin.employee_id)}` : kv('Admin', '—')}
                    </div>

                    <h4 style="margin:18px 0 8px">Plan &amp; subscription</h4>
                    <div class="grid">
                        ${kv('Plan', cap(o.plan))}${kv('Subscription', subs)}${kv('Trial ends', fmtDate(o.trial_ends_at))}${kv('Trial days left', o.on_trial ? String(o.trial_days_left) : '—')}
                        ${kv('Employees', o.employees_count + ' / ' + (o.employees_limit === null ? 'unlimited' : o.employees_limit))}${kv('Branches', o.branches_count + ' / ' + (o.branches_limit === null ? 'unlimited' : o.branches_limit))}
                    </div>

                    <h4 style="margin:18px 0 8px">Branches</h4>
                    ${o.branches.length === 0 ? '<div class="contact">No branches</div>' : o.branches.map(b => `<div class="branch">${esc(b.name)} <span>${b.lat}, ${b.lng} &middot; &plusmn;${b.radius_meters} m &middot; ${b.employee_count} employee${b.employee_count === 1 ? '' : 's'}</span></div>`).join('')}

                    <h4 style="margin:18px 0 8px">Employees (${o.employees.length})</h4>
                    ${o.employees.length === 0 ? '<div class="contact">No employees yet</div>' : `<table class="emps">
                        <thead><tr><th>Name</th><th>ID</th><th>Branch</th><th>Face</th><th>Status</th></tr></thead>
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

        function toggleSidebar(open) {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (open) {
                sidebar.classList.add('open');
                backdrop.classList.add('open');
            } else {
                sidebar.classList.remove('open');
                backdrop.classList.remove('open');
            }
        }

        let mode = 'orgs';

        function switchTab(targetMode, updateHash = true) {
            const validModes = ['orgs', 'subs', 'packages', 'promos', 'notifications'];
            if (!validModes.includes(targetMode)) targetMode = 'orgs';
            mode = targetMode;

            document.querySelectorAll('.nav-item').forEach(n => n.classList.toggle('active', n.dataset.mode === mode));
            
            // Auto close mobile drawer on selection
            if (window.innerWidth <= 992) {
                toggleSidebar(false);
            }

            const titles = {
                orgs: ['Organizations', 'Console · Organizations & Registrations'],
                subs: ['Subscriptions', 'Console · Organization Plans & Billing Status'],
                packages: ['Packages', 'Console · Subscription Packages & Pricing Tiers'],
                promos: ['Promos & Offers', 'Console · Promotional Codes & Discounts'],
                notifications: ['Push Broadcasts', 'Console · Live Alerts & Dispatch Center'],
            };
            const [title, breadcrumb] = titles[mode] || ['Console', 'SmartAttend'];
            document.getElementById('pageTitle').textContent = title;
            document.getElementById('pageBreadcrumb').textContent = breadcrumb;

            const orgArea = document.getElementById('orgArea');
            const subsArea = document.getElementById('subsArea');
            const pkArea = document.getElementById('packagesArea');
            const prArea = document.getElementById('promosArea');
            const notifArea = document.getElementById('notificationsArea');
            
            orgArea.style.display = mode === 'orgs' ? '' : 'none';
            subsArea.style.display = mode === 'subs' ? '' : 'none';
            pkArea.style.display = mode === 'packages' ? '' : 'none';
            prArea.style.display = mode === 'promos' ? '' : 'none';
            notifArea.style.display = mode === 'notifications' ? '' : 'none';

            if (updateHash && window.location.hash !== '#' + mode) {
                history.replaceState(null, null, '#' + mode);
            }

            if (mode === 'orgs') loadOrgs().catch(() => {});
            else if (mode === 'subs') loadSubs().catch(() => {});
            else if (mode === 'packages') loadPackages().catch(() => {});
            else if (mode === 'promos') loadPromos().catch(() => {});
            else if (mode === 'notifications') loadNotifications().catch(() => {});
        }

        document.getElementById('sidebarNav').addEventListener('click', (e) => {
            const btn = e.target.closest('.nav-item');
            if (!btn) return;
            switchTab(btn.dataset.mode, true);
        });

        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.replace(/^#/, '');
            if (hash && hash !== mode) {
                switchTab(hash, false);
            }
        });

        async function loadSubs() {
            const { organizations } = await api('/portal/api/organizations');
            const box = document.getElementById('subsArea');
            const list = organizations.filter(o => o.status !== 'pending');
            if (!list.length) { box.innerHTML = '<p style="color:var(--muted)">No subscriptions yet.</p>'; return; }
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
                        <span>Trial ends <b>${fmtDate(o.trial_ends_at)}</b></span>
                        <span>${o.on_trial ? o.trial_days_left + 'd left' : ''}</span>
                        <span>Employees ${o.employees_count}/${o.employees_limit === null ? '∞' : o.employees_limit}</span>
                        <span>Branches ${o.branches_count}/${o.branches_limit === null ? '∞' : o.branches_limit}</span>
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
            const box = document.getElementById('packagesArea');
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
                    <label class="switch"><input type="checkbox" id="pk_active_${p.id}" ${p.active ? 'checked' : ''}> Active (offered in registration & upgrades)</label>
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
            const box = document.getElementById('promosArea');
            const newCard = `<div class="org">
                <h3>Create promo</h3>
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
            const box = document.getElementById('notificationsArea');
            const stats = data.stats || {};
            const orgs = data.organizations || [];
            const broadcasts = data.recent_broadcasts || [];

            const statsHtml = `
                <div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
                    <div class="stat"><b>${stats.total_devices || 0}</b><span>Active Devices</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">${stats.android_devices || 0} Android &middot; ${stats.ios_devices || 0} iOS</small></div>
                    <div class="stat"><b>${stats.total_users || 0}</b><span>Total App Users</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">Eligible recipients</small></div>
                    <div class="stat"><b>${stats.broadcasts_count || 0}</b><span>Broadcasts Sent</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">All-time dispatched</small></div>
                </div>`;

            const orgOptions = orgs.map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');

            const composerHtml = `
                <div class="org" style="margin-bottom:20px">
                    <h3 style="display:flex;align-items:center;gap:8px">📣 Dispatch Push Notification</h3>
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
                    <input class="pfield" id="nb_title" maxlength="100" placeholder="e.g. Critical System Maintenance">

                    <label class="plabel">Message Body</label>
                    <textarea class="pfield" id="nb_body" rows="3" maxlength="500" placeholder="Enter broadcast announcement message..."></textarea>

                    <div class="actions" style="margin-top:14px">
                        <button class="btn primary" id="nb_submit_btn" onclick="sendBroadcast()">Dispatch Push Broadcast</button>
                    </div>
                </div>`;

            const historyRows = broadcasts.length === 0
                ? '<p style="color:var(--muted);margin-top:10px">No push broadcasts sent yet.</p>'
                : `<table class="emps" style="margin-top:12px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid rgba(32,15,53,.1)">
                    <thead>
                        <tr>
                            <th>Title &amp; Message</th>
                            <th>Audience</th>
                            <th>Sent By</th>
                            <th>Date &amp; Time</th>
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
                    <h3 style="font-size:16px;margin-bottom:8px">Recent Push Broadcasts</h3>
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

            if (!confirm(`Dispatch this push broadcast to ${audience === 'all' ? 'all users' : audience}?`)) return;

            const btn = document.getElementById('nb_submit_btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }

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
                if (btn) { btn.disabled = false; btn.textContent = 'Dispatch Push Broadcast'; }
            }
        }

        function quickBroadcast() {
            switchTab('notifications', true);
            setTimeout(() => {
                document.getElementById('nb_title')?.focus();
            }, 100);
        }

        async function syncAllData() {
            try {
                toast('Refreshing live data...', true);
                await loadStats();
                if (mode === 'orgs') await loadOrgs();
                else if (mode === 'subs') await loadSubs();
                else if (mode === 'packages') await loadPackages();
                else if (mode === 'promos') await loadPromos();
                else if (mode === 'notifications') await loadNotifications();
                toast('Data synced successfully', true);
            } catch (e) {
                toast(e.message || 'Sync error', false);
            }
        }

        function updateClock() {
            const clock = document.getElementById('liveClock');
            if (!clock) return;
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateStr = now.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
            clock.textContent = `${dateStr} · ${timeStr}`;
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
            updateClock();
            setInterval(updateClock, 1000);
            const initialHash = window.location.hash.replace(/^#/, '');
            if (initialHash && ['orgs', 'subs', 'packages', 'promos', 'notifications'].includes(initialHash)) {
                switchTab(initialHash, false);
            } else {
                switchTab('orgs', false);
            }
            try {
                await loadStats();
            } catch (e) { /* redirect handled */ }
        })();
    </script>
</body>
</html>
