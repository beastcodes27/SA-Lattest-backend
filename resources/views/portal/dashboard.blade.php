<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartAttend Admin &middot; Dashboard</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #EAF7EF;
            --ink: #200F35;
            --ink-2: #2F174B;
            --accent: #7C3AED;
            --accent-2: #A855F7;
            --sidebar-bg: #170826;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --sidebar-active: rgba(124, 58, 237, 0.24);
            --text: #1A1231;
            --muted: #5B5A73;
            --muted-light: #8A86A3;
            --green: #10B981;
            --amber: #F59E0B;
            --red: #EF4444;
            --card: #FFFFFF;
            --card-border: rgba(32, 15, 53, 0.08);
            --radius: 18px;
            --ring: 0 0 0 3px rgba(124, 58, 237, 0.18);
            --shadow-sm: 0 1px 2px rgba(24, 9, 44, 0.05), 0 6px 18px rgba(24, 9, 44, 0.05);
            --shadow-md: 0 12px 34px rgba(24, 9, 44, 0.10);
            --shadow-lg: 0 28px 70px rgba(24, 9, 44, 0.18);
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        ::selection { background: rgba(124, 58, 237, 0.22); }

        .app-layout { display: flex; min-height: 100vh; width: 100%; position: relative; }

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
            transition: transform 0.34s cubic-bezier(0.22, 1, 0.36, 1);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 10px 0 44px rgba(9, 3, 18, 0.38);
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }
        .brand-logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: var(--green);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B1F16;
            font-weight: 900;
            font-size: 17px;
            letter-spacing: -0.5px;
            box-shadow: 0 10px 26px rgba(16, 185, 129, 0.38);
        }
        .brand-text { display: flex; flex-direction: column; }
        .brand-title { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }

        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: #34D399; box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18), 0 0 10px #34D399; display: inline-block; }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-nav::-webkit-scrollbar { width: 6px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.12); border-radius: 6px; }
        .sidebar-nav::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.24); }
        .nav-section-title {
            font-size: 10.5px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.38);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 14px 12px 6px;
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
            color: rgba(255, 255, 255, 0.72);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
            position: relative;
        }
        .nav-item:hover { background: var(--sidebar-hover); color: #fff; transform: translateX(3px); }
        .nav-item:focus-visible { outline: 2px solid #6EE7B7; outline-offset: 1px; }
        .nav-item.active {
            background: var(--sidebar-active);
            color: #fff;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px rgba(167, 139, 250, 0.28);
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 7px;
            bottom: 7px;
            width: 4px;
            border-radius: 0 6px 6px 0;
            background: var(--green);
            box-shadow: 0 0 12px rgba(167, 139, 250, 0.9);
        }
        .nav-icon { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; color: inherit; }
        .nav-label { flex: 1; }
        .nav-badge {
            background: var(--red);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 999px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            background: rgba(0, 0, 0, 0.18);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 14px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.32);
            border-radius: 12px;
            color: #FCA5A5;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.18s ease;
        }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.22); color: #fff; transform: translateY(-1px); }

        /* Main view area */
        .app-main {
            flex: 1;
            margin-left: 270px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: calc(100% - 270px);
        }
        .main-topbar {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(16px) saturate(160%);
            border-bottom: 1px solid rgba(32, 15, 53, 0.07);
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
            border-radius: 10px;
            padding: 9px 12px;
            cursor: pointer;
            color: var(--ink);
            font-size: 16px;
        }
        .page-title { font-size: 19px; font-weight: 800; color: var(--ink); margin: 0; letter-spacing: -0.4px; }
        .page-breadcrumb { font-size: 12px; color: var(--muted); font-weight: 600; margin-top: 2px; }

        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .live-clock { font-size: 12px; font-weight: 700; color: var(--muted); font-variant-numeric: tabular-nums; display: none; }
        @media (min-width: 1200px) { .live-clock { display: block; } }
        .topbar-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--ink);
            color: #fff;
            border: 0;
            border-radius: 999px;
            padding: 8px 15px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            box-shadow: 0 6px 16px rgba(32, 15, 53, 0.25);
            transition: all 0.18s ease;
        }
        .topbar-action-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(32, 15, 53, 0.3); opacity: 0.96; }
        .topbar-action-btn.ghost { background: rgba(32, 15, 53, 0.05); color: var(--ink); box-shadow: none; border: 1px solid rgba(32, 15, 53, 0.08); }
        .topbar-action-btn.ghost:hover { background: rgba(32, 15, 53, 0.1); }
        .system-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            padding: 6px 13px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(16, 185, 129, 0.22);
        }
        .main-content {
            flex: 1;
            padding: 26px 28px 64px;
            max-width: 1420px;
            width: 100%;
            margin: 0 auto;
        }

        /* Sidebar backdrop for mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 3, 20, 0.55);
            z-index: 95;
            backdrop-filter: blur(3px);
        }

        /* Cards, Stats & Forms */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; margin-bottom: 26px; }
        .stat {
            position: relative;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            padding: 18px 18px 16px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: #7C3AED; }
        .stat:nth-child(4n+1)::before { background: #7C3AED; }
        .stat:nth-child(4n+2)::before { background: #10B981; }
        .stat:nth-child(4n+3)::before { background: #F59E0B; }
        .stat:nth-child(4n+4)::before { background: #F43F5E; }
        .stat b { font-size: 28px; color: var(--ink); display: block; letter-spacing: -0.8px; font-variant-numeric: tabular-nums; line-height: 1.1; }
        .stat span { color: var(--muted); font-size: 12px; font-weight: 700; margin-top: 4px; display: block; letter-spacing: 0.2px; }
        .stat small { color: var(--muted-light) !important; }
        .stat-btn { display: block; width: 100%; text-align: left; font: inherit; cursor: pointer; font-family: inherit; }
        .stat-btn:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }

        .filters { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter {
            border: 1px solid rgba(32, 15, 53, 0.12);
            background: var(--card);
            border-radius: 999px;
            padding: 9px 18px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            color: var(--muted);
            font-family: inherit;
            transition: all 0.18s ease;
        }
        .filter:hover { background: rgba(32, 15, 53, 0.04); color: var(--ink); }
        .filter.active { background: var(--ink); color: #fff; border-color: transparent; box-shadow: 0 6px 16px rgba(32, 15, 53, 0.25); }

        .org {
            position: relative;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            padding: 20px 22px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .org::before {
            content: '';
            position: absolute;
            left: 0;
            top: 18px;
            bottom: 18px;
            width: 4px;
            border-radius: 0 6px 6px 0;
            background: var(--accent);
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .org:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .org:hover::before { opacity: 1; }
        .org h3 { margin: 0 0 4px; font-size: 17px; color: var(--ink); letter-spacing: -0.3px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .org h4 { color: var(--ink); font-size: 13px; text-transform: uppercase; letter-spacing: 0.6px; }
        .org .contact { color: var(--muted); font-size: 13px; margin-bottom: 12px; }
        .meta { display: flex; gap: 14px; font-size: 12px; color: var(--muted); flex-wrap: wrap; align-items: center; }
        .pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 11px; font-size: 11.5px; font-weight: 800; }

        .actions { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
        .btn { border: 0; border-radius: 11px; padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.18s ease; }
        .btn.primary { background: var(--ink); color: #fff; box-shadow: 0 6px 16px rgba(32, 15, 53, 0.22); }
        .btn.primary:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(32, 15, 53, 0.3); }
        .btn.danger { background: rgba(239, 68, 68, 0.1); color: #DC2626; }
        .btn.danger:hover { background: rgba(239, 68, 68, 0.18); }
        .btn.ghost { background: rgba(32, 15, 53, 0.05); color: var(--ink); border: 1px solid rgba(32, 15, 53, 0.08); }
        .btn.ghost:hover { background: rgba(32, 15, 53, 0.1); }
        .btn[disabled] { opacity: 0.5; cursor: default; }

        /* Custom Toast Notification Container & Items */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 100020;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            width: calc(100% - 48px);
            pointer-events: none;
        }
        .toast-item {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 16px;
            border-radius: 16px;
            background: #FFFFFF;
            box-shadow: 0 16px 45px rgba(24, 9, 44, 0.16), 0 2px 8px rgba(24, 9, 44, 0.06);
            border: 1px solid rgba(32, 15, 53, 0.08);
            color: var(--text);
            font-size: 13.5px;
            line-height: 1.45;
            transform: translateY(-16px) scale(0.96);
            opacity: 0;
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        .toast-item.show {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .toast-item.hide {
            transform: translateY(-12px) scale(0.94);
            opacity: 0;
        }
        .toast-icon {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .toast-item.ok .toast-icon,
        .toast-item.success .toast-icon {
            background: rgba(16, 185, 129, 0.14);
            color: var(--green);
        }
        .toast-item.err .toast-icon,
        .toast-item.danger .toast-icon,
        .toast-item.error .toast-icon {
            background: rgba(239, 68, 68, 0.14);
            color: var(--red);
        }
        .toast-item.warning .toast-icon {
            background: rgba(245, 158, 11, 0.14);
            color: var(--amber);
        }
        .toast-item.info .toast-icon {
            background: rgba(124, 58, 237, 0.14);
            color: var(--accent);
        }
        .toast-content {
            flex: 1;
            min-width: 0;
            padding-top: 3px;
        }
        .toast-message {
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            word-break: break-word;
        }
        .toast-close {
            background: transparent;
            border: 0;
            color: var(--muted-light);
            cursor: pointer;
            padding: 4px;
            margin: -2px -4px 0 0;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s, background 0.15s;
        }
        .toast-close:hover {
            color: var(--ink);
            background: rgba(32, 15, 53, 0.06);
        }
        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            transform-origin: left;
            transition: width linear;
        }
        .toast-item.ok .toast-progress,
        .toast-item.success .toast-progress { background: var(--green); }
        .toast-item.err .toast-progress,
        .toast-item.danger .toast-progress,
        .toast-item.error .toast-progress { background: var(--red); }
        .toast-item.warning .toast-progress { background: var(--amber); }
        .toast-item.info .toast-progress { background: var(--accent); }

        /* Custom Modal Backdrop & Dialog */
        .app-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(18, 7, 30, 0.68);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 100010;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }
        .app-modal-backdrop.active {
            opacity: 1;
            visibility: visible;
        }
        .app-modal-dialog {
            width: 100%;
            max-width: 460px;
            background: #FFFFFF;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 32px 85px rgba(20, 8, 38, 0.32), 0 6px 20px rgba(20, 8, 38, 0.10);
            padding: 28px 26px 24px;
            position: relative;
            transform: scale(0.92) translateY(16px);
            opacity: 0;
            transition: transform 0.26s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease;
        }
        .app-modal-backdrop.active .app-modal-dialog {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        .app-modal-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
        }
        .app-modal-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .app-modal-icon.danger {
            background: rgba(239, 68, 68, 0.12);
            color: var(--red);
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.18);
        }
        .app-modal-icon.warning {
            background: rgba(245, 158, 11, 0.12);
            color: var(--amber);
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.18);
        }
        .app-modal-icon.info {
            background: rgba(124, 58, 237, 0.12);
            color: var(--accent);
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.18);
        }
        .app-modal-icon.success {
            background: rgba(16, 185, 129, 0.12);
            color: var(--green);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.18);
        }
        .app-modal-titles {
            flex: 1;
            padding-top: 3px;
        }
        .app-modal-title {
            font-size: 19px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.3px;
            margin: 0;
        }
        .app-modal-body {
            font-size: 14px;
            line-height: 1.55;
            color: var(--muted);
            margin-top: 6px;
            word-break: break-word;
        }
        .app-modal-input-wrap {
            margin-top: 16px;
        }
        .app-modal-input-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 7px;
        }
        .app-modal-input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(32, 15, 53, 0.16);
            font-size: 14.5px;
            font-family: inherit;
            color: var(--text);
            background: #FCFDFE;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .app-modal-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: var(--ring);
            background: #fff;
        }
        .app-modal-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
        }
        .app-modal-btn {
            border-radius: 12px;
            padding: 11px 18px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            border: 0;
            transition: all 0.18s cubic-bezier(0.22, 1, 0.36, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 90px;
        }
        .app-modal-btn.cancel {
            background: rgba(32, 15, 53, 0.05);
            color: var(--ink);
            border: 1px solid rgba(32, 15, 53, 0.1);
        }
        .app-modal-btn.cancel:hover {
            background: rgba(32, 15, 53, 0.1);
        }
        .app-modal-btn.confirm-primary {
            background: var(--ink);
            color: #fff;
            box-shadow: 0 4px 14px rgba(32, 15, 53, 0.22);
        }
        .app-modal-btn.confirm-primary:hover {
            background: var(--accent);
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(124, 58, 237, 0.3);
        }
        .app-modal-btn.confirm-danger {
            background: var(--red);
            color: #fff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.28);
        }
        .app-modal-btn.confirm-danger:hover {
            background: #DC2626;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(239, 68, 68, 0.38);
        }
        .app-modal-btn.confirm-success {
            background: var(--green);
            color: #0B1F16;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.28);
        }
        .app-modal-btn.confirm-success:hover {
            background: #059669;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(16, 185, 129, 0.38);
        }
        .app-modal-close {
            position: absolute;
            top: 18px;
            right: 18px;
            background: transparent;
            border: 0;
            color: var(--muted-light);
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s, background 0.15s;
        }
        .app-modal-close:hover {
            color: var(--ink);
            background: rgba(32, 15, 53, 0.06);
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .grid span { display: block; color: var(--muted-light); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 800; }
        .grid b { font-size: 13.5px; word-break: break-word; color: var(--ink); margin-top: 2px; display: block; }
        .branch {
            background: rgba(124, 58, 237, 0.05);
            border: 1px solid rgba(124, 58, 237, 0.14);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 8px;
        }
        .branch span { font-weight: 500; color: var(--muted); font-size: 12px; }
        .plan-warning {
            margin-top: 12px;
            background: rgba(194, 48, 48, 0.08);
            border: 1px solid rgba(194, 48, 48, 0.28);
            color: var(--red);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 12.5px;
            font-weight: 700;
        }
        .docs {
            background: rgba(32, 15, 53, 0.03);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 14px 16px;
        }
        .doc-label { display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 800; color: var(--muted); margin-bottom: 6px; }
        .doc-link { color: var(--accent); font-weight: 700; font-size: 13px; text-decoration: none; }
        .doc-link:hover { text-decoration: underline; }
        .doc-empty { color: var(--muted); font-size: 12.5px; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 8px; margin-top: 2px; }
        .photo-grid a { display: block; aspect-ratio: 1 / 1; border-radius: 10px; overflow: hidden; border: 1px solid var(--card-border); }
        .photo-grid img { width: 100%; height: 100%; object-fit: cover; display: block; }
        table.emps { width: 100%; border-collapse: collapse; font-size: 13px; overflow: hidden; border-radius: 12px; }
        table.emps th { text-align: left; background: rgba(32, 15, 53, 0.04); padding: 11px 14px; color: var(--muted); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 800; }
        table.emps td { padding: 11px 14px; border-bottom: 1px solid rgba(32, 15, 53, 0.06); color: var(--text); }
        table.emps tr:last-child td { border-bottom: 0; }
        table.emps tr:hover td { background: rgba(124, 58, 237, 0.03); }

        select { padding: 9px 12px; border-radius: 10px; border: 1px solid rgba(32, 15, 53, 0.16); background: #fff; font-weight: 600; font-family: inherit; color: var(--text); }
        .pfield {
            width: 100%;
            padding: 10px 13px;
            border-radius: 11px;
            border: 1px solid rgba(32, 15, 53, 0.16);
            font-size: 13.5px;
            margin-top: 5px;
            background: #fff;
            color: var(--text);
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .pfield:focus { outline: none; border-color: var(--accent); box-shadow: var(--ring); }
        .plabel { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: var(--muted); margin-top: 12px; }
        .switch { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13px; cursor: pointer; margin-top: 12px; color: var(--ink); }
        .switch input { accent-color: var(--accent); width: 16px; height: 16px; }

        @media (max-width: 992px) {
            .app-sidebar { transform: translateX(-100%); }
            .app-sidebar.open { transform: translateX(0); }
            .sidebar-backdrop.open { display: block; }
            .app-main { margin-left: 0; width: 100%; }
            .menu-toggle-btn { display: inline-flex; }
            .main-content { padding: 18px 16px 44px; }
            .main-topbar { padding: 12px 16px; }
            .system-pill { display: none; }
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
                <button class="nav-item" data-mode="sms">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    <span class="nav-label">Send SMS</span>
                </button>
                <button class="nav-item" data-mode="updates">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </span>
                    <span class="nav-label">App Updates</span>
                </button>

                <div class="nav-section-title">Account</div>
                <button class="nav-item" data-mode="account">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span class="nav-label">My Account</span>
                </button>
            </div>

            <div class="sidebar-footer">
                <form id="logoutForm" method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button class="logout-btn" type="button" onclick="confirmSignOut()">
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
                        <button class="filter active" data-status="">All</button>
                        <button class="filter" data-status="pending">Pending</button>
                        <button class="filter" data-status="active">Active</button>
                        <button class="filter" data-status="suspended">Suspended</button>
                    </div>
                    <div id="orgs"></div>
                </div>

                <div id="subsArea" style="display:none"></div>

                <div id="packagesArea" style="display:none"></div>

                <div id="promosArea" style="display:none"></div>

                <div id="notificationsArea" style="display:none"></div>

                <div id="smsArea" style="display:none"></div>
                <div id="updatesArea" style="display:none"></div>

                <div id="accountArea" style="display:none">
                    <div class="org" style="max-width:640px">
                        <h3>My Account</h3>
                        <div class="contact">
                            Signed in as <b>{{ auth()->user()->name }}</b> &middot; ID {{ auth()->user()->employee_id }} &middot; {{ auth()->user()->role }}
                        </div>
                        <h4 style="margin:18px 0 4px">Change Password</h4>
                        <p style="color:var(--muted);font-size:13px;margin:0">Use a strong password of at least 8 characters.</p>
                        <form id="passwordForm" onsubmit="changeAdminPassword(event)" autocomplete="off">
                            <label class="plabel" for="currentPassword">Current password</label>
                            <input class="pfield" id="currentPassword" type="password" autocomplete="current-password" required>
                            <label class="plabel" for="newPassword">New password</label>
                            <input class="pfield" id="newPassword" type="password" autocomplete="new-password" minlength="8" required>
                            <label class="plabel" for="confirmPassword">Confirm new password</label>
                            <input class="pfield" id="confirmPassword" type="password" autocomplete="new-password" minlength="8" required>
                            <div class="actions">
                                <button class="btn primary" type="submit" id="passwordSubmitBtn">Update password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Toast notification container -->
    <div class="toast-container" id="toastContainer" aria-live="polite"></div>

    <!-- Custom Modal Backdrop & Dialog -->
    <div class="app-modal-backdrop" id="appModalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="app-modal-dialog" id="appModalDialog">
            <button type="button" class="app-modal-close" id="appModalCloseBtn" onclick="closeAppModal(false)" aria-label="Close dialog">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="app-modal-header">
                <div class="app-modal-icon info" id="appModalIcon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                </div>
                <div class="app-modal-titles">
                    <h3 class="app-modal-title" id="appModalTitle">Confirm Action</h3>
                    <div class="app-modal-body" id="appModalBody">Are you sure you want to proceed?</div>
                </div>
            </div>
            <div class="app-modal-input-wrap" id="appModalInputWrap" style="display:none">
                <label class="app-modal-input-label" id="appModalInputLabel" for="appModalInput">Value</label>
                <input class="app-modal-input" id="appModalInput" type="text" autocomplete="off">
            </div>
            <div class="app-modal-actions">
                <button type="button" class="app-modal-btn cancel" id="appModalCancelBtn" onclick="closeAppModal(false)">Cancel</button>
                <button type="button" class="app-modal-btn confirm-primary" id="appModalConfirmBtn" onclick="confirmAppModal()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let current = '';
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const cap = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

        function setFilterButton() {
            document.querySelectorAll('.filter').forEach(f => {
                f.classList.toggle('active', f.dataset.status === current);
            });
        }

        /* Toast Popup Notification System */
        function toast(text, ok = true) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            let type = 'success';
            if (typeof ok === 'string') {
                type = ok;
            } else if (ok === false) {
                type = 'danger';
            }

            const toastEl = document.createElement('div');
            toastEl.className = `toast-item ${type}`;

            const icons = {
                success: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
                ok: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
                danger: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
                error: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
                err: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
                warning: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
                info: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
            };

            const iconHtml = icons[type] || icons.info;

            toastEl.innerHTML = `
                <div class="toast-icon">${iconHtml}</div>
                <div class="toast-content">
                    <div class="toast-message">${esc(text)}</div>
                </div>
                <button type="button" class="toast-close" title="Dismiss" aria-label="Dismiss">&times;</button>
                <div class="toast-progress"></div>
            `;

            container.appendChild(toastEl);
            requestAnimationFrame(() => toastEl.classList.add('show'));

            let dismissTimer;
            const duration = 4000;
            const progressEl = toastEl.querySelector('.toast-progress');
            if (progressEl) {
                progressEl.style.transition = `width ${duration}ms linear`;
                requestAnimationFrame(() => { progressEl.style.width = '0%'; });
            }

            const remove = () => {
                clearTimeout(dismissTimer);
                toastEl.classList.remove('show');
                toastEl.classList.add('hide');
                setTimeout(() => toastEl.remove(), 280);
            };

            toastEl.querySelector('.toast-close').addEventListener('click', remove);
            dismissTimer = setTimeout(remove, duration);
        }

        toast.success = (msg) => toast(msg, 'success');
        toast.error = (msg) => toast(msg, 'danger');
        toast.warning = (msg) => toast(msg, 'warning');
        toast.info = (msg) => toast(msg, 'info');

        /* Custom Modal Dialog & Popups System */
        let activeModalResolver = null;

        function showAppModal({
            title = 'Confirm Action',
            message = '',
            tone = 'info',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            showCancel = true,
            input = null,
        } = {}) {
            return new Promise((resolve) => {
                const backdrop = document.getElementById('appModalBackdrop');
                const iconEl = document.getElementById('appModalIcon');
                const titleEl = document.getElementById('appModalTitle');
                const bodyEl = document.getElementById('appModalBody');
                const inputWrap = document.getElementById('appModalInputWrap');
                const inputEl = document.getElementById('appModalInput');
                const inputLabel = document.getElementById('appModalInputLabel');
                const cancelBtn = document.getElementById('appModalCancelBtn');
                const confirmBtn = document.getElementById('appModalConfirmBtn');

                activeModalResolver = resolve;

                const icons = {
                    danger: `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
                    warning: `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                    success: `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
                    info: `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
                };

                iconEl.className = `app-modal-icon ${tone}`;
                iconEl.innerHTML = icons[tone] || icons.info;

                titleEl.textContent = title;
                bodyEl.innerHTML = esc(message).replace(/\n/g, '<br>');

                if (input) {
                    inputWrap.style.display = 'block';
                    inputLabel.textContent = input.label || 'Value';
                    inputEl.value = input.value ?? '';
                    inputEl.placeholder = input.placeholder || '';
                    inputEl.type = input.type || 'text';
                } else {
                    inputWrap.style.display = 'none';
                }

                cancelBtn.style.display = showCancel ? 'inline-flex' : 'none';
                cancelBtn.textContent = cancelText;

                confirmBtn.className = `app-modal-btn confirm-${tone === 'danger' ? 'danger' : tone === 'success' ? 'success' : 'primary'}`;
                confirmBtn.textContent = confirmText;

                backdrop.classList.add('active');

                if (input) {
                    setTimeout(() => { inputEl.focus(); inputEl.select(); }, 60);
                } else {
                    setTimeout(() => confirmBtn.focus(), 60);
                }
            });
        }

        function closeAppModal(result) {
            const backdrop = document.getElementById('appModalBackdrop');
            if (!backdrop) return;
            backdrop.classList.remove('active');
            if (activeModalResolver) {
                const res = activeModalResolver;
                activeModalResolver = null;
                res(result);
            }
        }

        function confirmAppModal() {
            const inputWrap = document.getElementById('appModalInputWrap');
            const inputEl = document.getElementById('appModalInput');
            if (inputWrap && inputWrap.style.display !== 'none') {
                closeAppModal(inputEl ? inputEl.value : true);
            } else {
                closeAppModal(true);
            }
        }

        function customConfirm(message, { title = 'Are you sure?', tone = 'warning', confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
            return showAppModal({ title, message, tone, confirmText, cancelText, showCancel: true });
        }

        function customAlert(message, { title = 'Notice', tone = 'info', buttonText = 'OK' } = {}) {
            return showAppModal({ title, message, tone, confirmText: buttonText, showCancel: false });
        }

        function customPrompt(message, defaultValue = '', { title = 'Enter Value', label = 'Value', inputType = 'text', placeholder = '', confirmText = 'OK', cancelText = 'Cancel' } = {}) {
            return new Promise(async (resolve) => {
                const inputWrap = document.getElementById('appModalInput');
                const result = await showAppModal({
                    title,
                    message,
                    tone: 'info',
                    confirmText,
                    cancelText,
                    showCancel: true,
                    input: { label, value: defaultValue, placeholder, type: inputType },
                });
                if (result !== false && result !== null) {
                    resolve(typeof result === 'string' ? result : (inputWrap ? inputWrap.value : defaultValue));
                } else {
                    resolve(null);
                }
            });
        }

        async function confirmSignOut() {
            const ok = await customConfirm('Are you sure you want to sign out of the System Admin Console?', {
                title: 'Sign Out',
                tone: 'warning',
                confirmText: 'Sign Out',
                cancelText: 'Stay Signed In',
            });
            if (ok) {
                document.getElementById('logoutForm').submit();
            }
        }

        document.addEventListener('keydown', (e) => {
            const backdrop = document.getElementById('appModalBackdrop');
            if (!backdrop || !backdrop.classList.contains('active')) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                closeAppModal(false);
            } else if (e.key === 'Enter') {
                if (e.target && e.target.tagName !== 'BUTTON') {
                    e.preventDefault();
                    confirmAppModal();
                }
            }
        });

        document.getElementById('appModalBackdrop')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('appModalBackdrop')) {
                closeAppModal(false);
            }
        });

        async function api(path, opts = {}) {
            const res = await fetch(path, {
                headers: opts.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } : { 'Accept': 'application/json' },
                ...opts,
            });
            if (res.status === 401 || res.status === 419) { location.href = '/portal'; throw new Error('expired'); }
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                let message = json.message || 'Request failed';
                if (res.status === 404 && /Organization/i.test(message)) {
                    message = 'That organization no longer exists. The list has been refreshed.';
                    setTimeout(() => { if (typeof loadOrgs === 'function') loadOrgs().catch(() => {}); }, 200);
                }
                throw new Error(message);
            }
            return json;
        }

        async function changeAdminPassword(event) {
            event.preventDefault();

            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword.length < 8) {
                toast.warning('New password must be at least 8 characters.');
                return;
            }
            if (newPassword !== confirmPassword) {
                toast.warning('New password and confirmation do not match.');
                return;
            }

            const btn = document.getElementById('passwordSubmitBtn');
            btn.disabled = true;

            try {
                const res = await api('/portal/api/password', {
                    method: 'POST',
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                        new_password_confirmation: confirmPassword,
                    }),
                });
                document.getElementById('passwordForm').reset();
                toast.success(res.message || 'Password changed successfully.');
            } catch (err) {
                toast.error(err.message || 'Could not change password.');
            } finally {
                btn.disabled = false;
            }
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
                const noPlan = o.status === 'active' && ['cancelled', 'expired', 'none'].includes(o.plan_state);
                const planWarning = noPlan
                    ? `<div style="color:var(--red);font-size:12px;font-weight:700;margin-top:8px">No active plan &middot; ${o.plan_state === 'cancelled' ? 'subscription canceled' : o.plan_state === 'expired' ? 'plan expired' : 'never subscribed'}</div>`
                    : '';
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
                    ${planWarning}
                    <div class="actions">${view}${buttons}<button class="btn danger" onclick="deleteOrg(${o.id})">Delete</button></div>
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
            const raw = await customPrompt('Set the initial free trial duration in days for this organization.', '30', {
                title: 'Approve Organization',
                label: 'Trial Length (Days)',
                inputType: 'number',
                placeholder: '30',
                confirmText: 'Next',
            });
            if (raw === null) return;
            const days = parseInt(raw, 10);
            if (!days || days < 1 || days > 365) {
                toast.warning('Enter a trial length between 1 and 365 days.');
                return;
            }
            const ok = await customConfirm(`Approve this organization and start a ${days}-day free trial?`, {
                title: 'Confirm Approval',
                tone: 'success',
                confirmText: 'Approve & Start Trial',
            });
            if (!ok) return;
            try {
                const json = await api(`/portal/api/organizations/${id}/approve`, { method: 'POST', body: JSON.stringify({ days }) });
                toast.success(json.message || 'Organization approved');
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) {
                toast.error(e.message);
            }
        }

        async function act(id, type) {
            const configs = {
                reject: {
                    title: 'Reject Registration',
                    message: 'Reject this registration request? The applicant admin will be notified via SMS and push notification.',
                    tone: 'danger',
                    confirmText: 'Reject Registration',
                    action: () => api(`/portal/api/organizations/${id}/reject`, { method: 'POST' }),
                },
                suspend: {
                    title: 'Suspend Organization',
                    message: 'Suspend this organization? All employees and branch members will lose access immediately.',
                    tone: 'danger',
                    confirmText: 'Suspend Organization',
                    action: () => api(`/portal/api/organizations/${id}/status`, { method: 'POST', body: JSON.stringify({ status: 'suspended' }) }),
                },
                reactivate: {
                    title: 'Reactivate Organization',
                    message: 'Reactivate this organization? Full access will be restored for all users and branches.',
                    tone: 'success',
                    confirmText: 'Reactivate',
                    action: () => api(`/portal/api/organizations/${id}/status`, { method: 'POST', body: JSON.stringify({ status: 'active' }) }),
                },
            }[type];
            if (!configs) return;
            const ok = await customConfirm(configs.message, {
                title: configs.title,
                tone: configs.tone,
                confirmText: configs.confirmText,
            });
            if (!ok) return;
            try {
                const json = await configs.action();
                toast.success(json.message || 'Done');
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) {
                toast.error(e.message);
            }
        }

        async function deleteOrg(id) {
            const ok = await customConfirm('Delete this organization and ALL of its data (users, branches, attendance and payments)? This action is permanent and cannot be undone.', {
                title: 'Delete Organization',
                tone: 'danger',
                confirmText: 'Delete Permanently',
            });
            if (!ok) return;
            try {
                const json = await api('/portal/api/organizations/' + id, { method: 'DELETE' });
                toast.success(json.message || 'Organization deleted.');
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) {
                if (e.message !== 'expired') toast.error(e.message);
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
                    ${o.status === 'active' && ['cancelled', 'expired', 'none'].includes(o.plan_state) ? `<div class="plan-warning">No active plan (${esc(o.plan_state)}). Management features are locked until this organization subscribes.</div>` : ''}

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
                        ${kv('Plan', cap(o.plan))}${kv('Subscription', subs)}${kv('Plan state', o.plan_state)}${kv('Trial ends', fmtDate(o.trial_ends_at))}${kv('Trial days left', o.on_trial ? String(o.trial_days_left) : '—')}
                        ${kv('Employees', o.employees_count + ' / ' + (o.employees_limit === null ? 'unlimited' : o.employees_limit))}${kv('Branches', o.branches_count + ' / ' + (o.branches_limit === null ? 'unlimited' : o.branches_limit))}
                    </div>

                    <h4 style="margin:18px 0 8px">Verification documents</h4>
                    <div class="docs">
                        <div>
                            <span class="doc-label">TIN / Registration document</span>
                            ${o.tin_document_url ? `<a class="doc-link" href="${esc(o.tin_document_url)}" target="_blank" rel="noopener">${esc(o.tin_document_name || 'Open document')} &#8599;</a>` : '<span class="doc-empty">No TIN document uploaded</span>'}
                        </div>
                        <div style="margin-top:12px">
                            <span class="doc-label">Registration photos (${(o.photos || []).length})</span>
                            ${(o.photos || []).length
                                ? `<div class="photo-grid">${(o.photos || []).map((url, i) => `<a href="${esc(url)}" target="_blank" rel="noopener"><img src="${esc(url)}" alt="Registration photo ${i + 1}" loading="lazy" /></a>`).join('')}</div>`
                                : '<span class="doc-empty">No photos uploaded</span>'}
                        </div>
                    </div>

                    <h4 style="margin:18px 0 8px">Branches</h4>
                    ${o.branches.length === 0 ? '<div class="contact">No branches</div>' : o.branches.map(b => `<div class="branch">${esc(b.name)} <span>${b.lat}, ${b.lng} &middot; &plusmn;${b.radius_meters} m &middot; ${b.employee_count} employee${b.employee_count === 1 ? '' : 's'}</span></div>`).join('')}

                    <h4 style="margin:18px 0 8px">Employees (${o.employees.length})</h4>
                    ${o.employees.length === 0 ? '<div class="contact">No employees yet</div>' : `<table class="emps">
                        <thead><tr><th>Name</th><th>ID</th><th>Branch</th><th>Face</th><th>Status</th></tr></thead>
                        <tbody>${o.employees.map(e => `<tr><td>${esc(e.name)}</td><td>${esc(e.employee_id)}</td><td>${esc(e.branch)}</td><td>${e.face_enrolled ? 'Yes' : 'No'}</td><td>${e.active ? 'Active' : 'Inactive'}</td></tr>`).join('')}</tbody>
                    </table>`}
                    <div class="actions">${actions}<button class="btn danger" onclick="deleteOrg(${o.id})">Delete organization</button></div>
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
        let pendingPromoCompose = null;

        function emailPromo(id) {
            pendingPromoCompose = String(id);
            switchTab('notifications', true);
        }

        function switchTab(targetMode, updateHash = true) {
            const validModes = ['orgs', 'subs', 'packages', 'promos', 'notifications', 'sms', 'updates', 'account'];
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
                sms: ['Send SMS', 'Console · Text Messaging to Organization Admins'],
                updates: ['App Updates', 'Console · Mobile Version Control & OTA Rollouts'],
                account: ['My Account', 'Console · System Admin Profile & Password'],
            };
            const [title, breadcrumb] = titles[mode] || ['Console', 'SmartAttend'];
            document.getElementById('pageTitle').textContent = title;
            document.getElementById('pageBreadcrumb').textContent = breadcrumb;

            const orgArea = document.getElementById('orgArea');
            const subsArea = document.getElementById('subsArea');
            const pkArea = document.getElementById('packagesArea');
            const prArea = document.getElementById('promosArea');
            const notifArea = document.getElementById('notificationsArea');
            const smsArea = document.getElementById('smsArea');
            const updArea = document.getElementById('updatesArea');
            const accArea = document.getElementById('accountArea');
            
            orgArea.style.display = mode === 'orgs' ? '' : 'none';
            subsArea.style.display = mode === 'subs' ? '' : 'none';
            pkArea.style.display = mode === 'packages' ? '' : 'none';
            prArea.style.display = mode === 'promos' ? '' : 'none';
            notifArea.style.display = mode === 'notifications' ? '' : 'none';
            smsArea.style.display = mode === 'sms' ? '' : 'none';
            if (updArea) updArea.style.display = mode === 'updates' ? '' : 'none';
            if (accArea) accArea.style.display = mode === 'account' ? '' : 'none';

            if (updateHash && window.location.hash !== '#' + mode) {
                history.replaceState(null, null, '#' + mode);
            }

            if (mode === 'orgs') loadOrgs().catch(() => {});
            else if (mode === 'subs') loadSubs().catch(() => {});
            else if (mode === 'packages') loadPackages().catch(() => {});
            else if (mode === 'promos') loadPromos().catch(() => {});
            else if (mode === 'notifications') loadNotifications().catch(() => {});
            else if (mode === 'sms') loadSms().catch(() => {});
            else if (mode === 'updates') loadAppVersion().catch(() => {});
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
                    : (o.subscription_status === 'active' && o.subscription_ends_at && new Date(o.subscription_ends_at) < new Date()) ? 'Expired'
                    : o.on_trial ? 'Free trial'
                    : o.subscription_status === 'active' ? 'Active' : 'No trial';
                const subColor = o.subscription_status === 'canceled' ? 'var(--red)' : (o.subscription_status === 'active' || o.on_trial ? 'var(--green)' : 'var(--amber)');
                const select = `<select id="plan_${o.id}" style="font-weight:700">${['starter','business','enterprise'].map(p => `<option value="${p}" ${p === o.plan ? 'selected' : ''}>${cap(p)}</option>`).join('')}</select>`;
                const manage = o.status === 'suspended'
                    ? '<div style="color:var(--red);font-size:12px;font-weight:700">Organization suspended</div>'
                    : `<button class="btn ghost" onclick="subAction(${o.id},'set_plan')">Set plan</button>
                       <button class="btn ghost" onclick="extendTrial(${o.id})">Extend trial</button>
                       ${o.subscription_status === 'canceled'
                            ? `<button class="btn primary" onclick="subAction(${o.id},'renew')">Resume subscription</button>`
                            : `<button class="btn danger" onclick="subAction(${o.id},'cancel')">Cancel</button>`}`;
                const pausedLine = o.subscription_status === 'canceled' && o.paused_plan
                    ? `<span style="color:var(--red);font-weight:700">Paused package: ${cap(o.paused_plan)}${o.paused_days_left ? ' · ' + o.paused_days_left + 'd left' : ''}</span>`
                    : '';
                const periodLine = o.subscription_status === 'active' && o.subscription_ends_at
                    ? `<span>Package ends <b>${fmtDate(o.subscription_ends_at)}</b></span>`
                    : '';
                return `<div class="org">
                    <h3>${esc(o.name)} <span class="pill" style="color:${subColor};background:${subColor}1A">${sub}</span></h3>
                    <div class="meta">
                        <span>Plan: <b>${cap(o.plan)}</b></span>
                        <span>Trial ends <b>${fmtDate(o.trial_ends_at)}</b></span>
                        <span>${o.on_trial ? o.trial_days_left + 'd left' : ''}</span>
                        ${periodLine}
                        ${pausedLine}
                        <span>Employees ${o.employees_count}/${o.employees_limit === null ? '∞' : o.employees_limit}</span>
                        <span>Branches ${o.branches_count}/${o.branches_limit === null ? '∞' : o.branches_limit}</span>
                    </div>
                    <div class="actions">${select}${manage}</div>
                </div>`;
            }).join('');
        }

        async function extendTrial(id) {
            const raw = await customPrompt('How many days would you like to add to this trial?', '30', {
                title: 'Extend Free Trial',
                label: 'Additional Trial Days',
                inputType: 'number',
                placeholder: '30',
                confirmText: 'Extend Trial',
            });
            if (raw === null) return;
            const days = parseInt(raw, 10);
            if (!days || days < 1 || days > 365) {
                toast.warning('Enter between 1 and 365 days.');
                return;
            }
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
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div><label class="plabel">Monthly price (numbers only)</label>
                        <input class="pfield" id="pk_month_${p.id}" type="number" min="0" value="${p.monthly_price || 0}"></div>
                        <div><label class="plabel">Annual price (numbers only)</label>
                        <input class="pfield" id="pk_year_${p.id}" type="number" min="0" value="${p.annual_price || 0}"></div>
                    </div>
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
                monthly_price: val('pk_month_' + id) ? parseInt(val('pk_month_' + id), 10) : 0,
                annual_price: val('pk_year_' + id) ? parseInt(val('pk_year_' + id), 10) : 0,
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
                    <div class="actions"><button class="btn primary" onclick="updatePromo(${p.id})">Save</button>${p.active ? `<button class="btn ghost" onclick="emailPromo(${p.id})">Email Promo</button>` : ''}<button class="btn danger" onclick="deletePromo(${p.id})">Delete</button></div>
                </div>`;
            }).join('');
            box.innerHTML = newCard + list;
        }

        async function deletePromo(id) {
            if (!confirm('Delete this promo code? Its redemption history will also be removed. This cannot be undone.')) return;
            try {
                const json = await api('/portal/api/promos/' + id, { method: 'DELETE' });
                toast(json.message || 'Promo deleted.', true);
                await loadPromos();
            } catch (e) {
                if (e.message !== 'expired') toast(e.message, false);
            }
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
            const promos = data.promos || [];

            const statsHtml = `
                <div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
                    <div class="stat"><b>${stats.total_devices || 0}</b><span>Active Devices</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">${stats.android_devices || 0} Android &middot; ${stats.ios_devices || 0} iOS</small></div>
                    <div class="stat"><b>${stats.total_users || 0}</b><span>Total App Users</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">Eligible recipients</small></div>
                    <div class="stat"><b>${stats.broadcasts_count || 0}</b><span>Broadcasts Sent</span><small style="color:var(--muted);font-size:11px;display:block;margin-top:2px">All-time dispatched</small></div>
                </div>`;

            const orgOptions = orgs.map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');
            const promoOptions = promos.map(p => `<option value="${p.id}">${esc(p.code)} — ${esc(p.title)}</option>`).join('');

            const composerHtml = `
                <div class="org" style="margin-bottom:20px">
                    <h3 style="display:flex;align-items:center;gap:8px">Dispatch Announcement</h3>
                    <p style="color:var(--muted);font-size:13px;margin:4px 0 14px">Send an in-app push alert, an SMS, and/or a branded email announcement to the selected audience.</p>
                    
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

                    <label class="switch" style="margin-top:12px"><input type="checkbox" id="nb_sms"> Also send as SMS to recipients' phone numbers</label>
                    <label class="switch" style="margin-top:8px"><input type="checkbox" id="nb_email" onchange="togglePromoSelect(this.checked)"> Also send as a branded email</label>

                    <div id="nb_promo_wrap" style="display:none;margin-top:4px">
                        <label class="plabel">Attach a promotion (optional)</label>
                        <select class="pfield" id="nb_promo_id">
                            <option value="">No promotion — plain announcement</option>
                            ${promoOptions}
                        </select>
                        <div style="color:var(--muted);font-size:11px;margin-top:4px">The promo code, offer and expiry date will be included in the email.</div>
                    </div>

                    <div class="actions" style="margin-top:14px">
                        <button class="btn primary" id="nb_submit_btn" onclick="sendBroadcast()">Dispatch Announcement</button>
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
                            <th></th>
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
                                <td><button class="btn danger" onclick="deleteBroadcast(${b.id})">Delete</button></td>
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
            applyPendingPromoCompose(promos);
        }

        function togglePromoSelect(show) {
            const wrap = document.getElementById('nb_promo_wrap');
            if (wrap) wrap.style.display = show ? '' : 'none';
            if (!show) {
                const select = document.getElementById('nb_promo_id');
                if (select) select.value = '';
            }
        }

        function applyPendingPromoCompose(promos) {
            if (!pendingPromoCompose) return;

            const promo = (promos || []).find(p => String(p.id) === pendingPromoCompose);
            pendingPromoCompose = null;
            if (!promo) return;

            const email = document.getElementById('nb_email');
            if (email) email.checked = true;
            togglePromoSelect(true);

            const promoSelect = document.getElementById('nb_promo_id');
            if (promoSelect) promoSelect.value = String(promo.id);

            const audience = document.getElementById('nb_audience');
            if (audience) audience.value = 'org_admin';

            const title = document.getElementById('nb_title');
            if (title && !title.value) title.value = promo.title;

            const body = document.getElementById('nb_body');
            if (body && !body.value) {
                body.value = `${promo.title}. Use promo code ${promo.code} to get ${promo.label.toLowerCase()}.`;
            }
        }

        async function loadSms() {
            const box = document.getElementById('smsArea');
            let orgOptions = '';
            try {
                const { organizations } = await api('/portal/api/organizations');
                orgOptions = (organizations || []).map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');
            } catch { /* ignore */ }

            box.innerHTML = `
                <div class="org" style="max-width:640px">
                    <h3>Send SMS</h3>
                    <p style="color:var(--muted);font-size:13px;margin:4px 0 14px">Text organization admins (or another audience) that have a phone number on file.</p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label class="plabel">Audience</label>
                            <select class="pfield" id="sms_audience" onchange="toggleSmsOrgSelect(this.value)">
                                <option value="org_admin">Organization Admins Only</option>
                                <option value="employee">Employees Only</option>
                                <option value="all">All Users</option>
                                <option value="org">Specific Organization</option>
                            </select>
                        </div>
                        <div id="sms_org_wrap" style="display:none">
                            <label class="plabel">Target Organization</label>
                            <select class="pfield" id="sms_org_id">${orgOptions}</select>
                        </div>
                    </div>

                    <label class="plabel">Message</label>
                    <textarea class="pfield" id="sms_body" rows="4" maxlength="480" placeholder="Type your SMS message..."></textarea>
                    <div style="color:var(--muted);font-size:11px;margin-top:4px"><span id="sms_count">0</span>/480 characters</div>

                    <div class="actions" style="margin-top:14px">
                        <button class="btn primary" id="sms_send_btn" onclick="sendSmsNow()">Send SMS</button>
                    </div>
                </div>`;

            const body = document.getElementById('sms_body');
            if (body) {
                body.addEventListener('input', () => {
                    const el = document.getElementById('sms_count');
                    if (el) el.textContent = String(body.value.length);
                });
            }
        }

        function toggleSmsOrgSelect(audience) {
            const wrap = document.getElementById('sms_org_wrap');
            if (wrap) wrap.style.display = audience === 'org' ? '' : 'none';
        }

        async function sendSmsNow() {
            const message = document.getElementById('sms_body')?.value.trim();
            const audience = document.getElementById('sms_audience')?.value || 'org_admin';
            const orgId = audience === 'org' ? document.getElementById('sms_org_id')?.value : null;

            if (!message) { toast.warning('Please enter an SMS message.'); return; }
            const ok = await customConfirm('Send this SMS to the selected recipients?', {
                title: 'Send SMS Broadcast',
                tone: 'info',
                confirmText: 'Send SMS',
            });
            if (!ok) return;

            const btn = document.getElementById('sms_send_btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }
            try {
                const json = await api('/portal/api/notifications/sms', {
                    method: 'POST',
                    body: JSON.stringify({
                        message,
                        role: ['all', 'org'].includes(audience) ? undefined : audience,
                        org_id: orgId ? parseInt(orgId, 10) : undefined,
                    }),
                });
                toast(json.message || 'SMS sent.', true);
            } catch (e) {
                toast(e.message || 'Failed to send SMS.', false);
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Send SMS'; }
            }
        }

        async function deleteBroadcast(id) {
            const ok = await customConfirm('Delete this broadcast from the history? This removes all of its recipient records.', {
                title: 'Delete Broadcast',
                tone: 'danger',
                confirmText: 'Delete Broadcast',
            });
            if (!ok) return;
            try {
                const json = await api('/portal/api/notifications/' + id, { method: 'DELETE' });
                toast(json.message || 'Broadcast deleted.', true);
                await loadNotifications();
            } catch (e) {
                if (e.message !== 'expired') toast(e.message, false);
            }
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
            const sendSms = !!document.getElementById('nb_sms')?.checked;
            const sendEmail = !!document.getElementById('nb_email')?.checked;
            const promoId = sendEmail ? document.getElementById('nb_promo_id')?.value : null;

            if (!title) { toast.warning('Please enter a notification title.'); return; }
            if (!body) { toast.warning('Please enter a message body.'); return; }

            const targetDesc = audience === 'all' ? 'all users' : audience;
            const channels = ['push', sendSms ? 'SMS' : null, sendEmail ? 'email' : null].filter(Boolean).join(' + ');
            const ok = await customConfirm(`Dispatch this ${channels} announcement to ${targetDesc}?`, {
                title: 'Dispatch Announcement',
                tone: 'warning',
                confirmText: 'Dispatch Announcement',
            });
            if (!ok) return;

            const btn = document.getElementById('nb_submit_btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }

            try {
                const payload = {
                    title,
                    body,
                    org_id: orgId ? parseInt(orgId, 10) : undefined,
                    role: ['all', 'org'].includes(audience) ? undefined : audience,
                    send_sms: sendSms,
                    send_email: sendEmail,
                    promo_id: promoId ? parseInt(promoId, 10) : undefined,
                };
                const json = await api('/portal/api/notifications/broadcast', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                toast(json.message || 'Announcement sent successfully!', true);
                await loadNotifications();
            } catch (e) {
                toast(e.message || 'Failed to dispatch announcement.', false);
                if (btn) { btn.disabled = false; btn.textContent = 'Dispatch Announcement'; }
            }
        }

        function quickBroadcast() {
            switchTab('notifications', true);
            setTimeout(() => {
                document.getElementById('nb_title')?.focus();
            }, 100);
        }

        let currentVersionPlatform = 'android';

        async function loadAppVersion(targetPlatform = currentVersionPlatform) {
            currentVersionPlatform = targetPlatform;
            const box = document.getElementById('updatesArea');
            if (!box) return;
            box.innerHTML = `<div class="org" style="text-align:center;padding:36px;color:var(--muted)">Loading app version settings...</div>`;
            try {
                const res = await api(`/portal/api/app-version?platform=${encodeURIComponent(targetPlatform)}`);
                const cfg = res.config || {};
                const isForced = !!cfg.force_update;
                const platformLabel = targetPlatform === 'ios' ? 'Apple iOS' : 'Google Android';

                box.innerHTML = `
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap">
                        <div style="display:flex;gap:8px;background:rgba(32,15,53,0.06);padding:4px;border-radius:12px">
                            <button type="button" class="btn ${targetPlatform === 'android' ? 'primary' : 'secondary'}" style="padding:7px 18px;font-size:13px;border-radius:8px" onclick="loadAppVersion('android')">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px"><path d="M17 19H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4Z"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                                Android APK
                            </button>
                            <button type="button" class="btn ${targetPlatform === 'ios' ? 'primary' : 'secondary'}" style="padding:7px 18px;font-size:13px;border-radius:8px" onclick="loadAppVersion('ios')">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z"/></svg>
                                Apple iOS
                            </button>
                        </div>
                        <div style="font-size:12.5px;color:var(--muted)">
                            Platform: <strong style="color:var(--ink)">${platformLabel}</strong> &middot; Last updated: ${cfg.updated_at ? fmtDate(cfg.updated_at) : 'recently'}
                        </div>
                    </div>

                    <div class="org" style="margin-bottom:16px">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap">
                            <div>
                                <h3 style="margin:0 0 4px">Version Control &amp; Rollout Configuration</h3>
                                <p style="color:var(--muted);font-size:13px;margin:0">Set the active version numbers and control whether updates are optional (soft) or mandatory (forced).</p>
                            </div>
                            <span class="pill" style="font-size:12px;padding:4px 10px;background:${isForced ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)'};color:${isForced ? 'var(--red)' : 'var(--green)'}">
                                ${isForced ? 'Force Update Active' : 'Normal / Soft Updates'}
                            </span>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px">
                            <div>
                                <label class="plabel">Latest Available Version</label>
                                <input class="pfield" id="ver_latest" value="${esc(cfg.latest_version || '1.0.0')}" placeholder="e.g. 1.0.4">
                                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">Any installed app older than this version is prompted with an update.</div>
                            </div>
                            <div>
                                <label class="plabel">Minimum Required Version</label>
                                <input class="pfield" id="ver_min" value="${esc(cfg.min_version || '1.0.0')}" placeholder="e.g. 1.0.0">
                                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">Installed apps older than this version are <strong>strictly forced</strong> to update before using the app.</div>
                            </div>
                        </div>

                        <div style="margin-top:14px;padding:12px 14px;border-radius:10px;background:rgba(32,15,53,0.03);border:1px solid rgba(32,15,53,0.06)">
                            <label class="switch" style="font-weight:600;display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" id="ver_force" ${isForced ? 'checked' : ''}>
                                <span>Force all older versions to update immediately (Mandatory Update)</span>
                            </label>
                            <div style="font-size:11.5px;color:var(--muted);margin-top:4px;margin-left:26px">
                                When enabled, anyone with a version lower than Latest Version cannot dismiss the dialog or access the app.
                            </div>
                        </div>

                        <div style="margin-top:14px">
                            <label class="plabel">Update Dialog Title</label>
                            <input class="pfield" id="ver_title" value="${esc(cfg.title || 'Update Available')}" placeholder="e.g. New Version Available">
                        </div>

                        <div style="margin-top:14px">
                            <label class="plabel">Direct APK Download Link (Android)</label>
                            <input class="pfield" id="ver_apk" value="${esc(cfg.apk_url || '')}" placeholder="https://sa.devtz.com/downloads/SmartAttend.apk or EAS build download URL">
                            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">When user taps "Update Now" on Android, this APK file is downloaded/opened directly.</div>
                        </div>

                        <div style="margin-top:14px">
                            <label class="plabel">Play Store / App Store URL (Fallback)</label>
                            <input class="pfield" id="ver_store" value="${esc(cfg.store_url || '')}" placeholder="https://play.google.com/store/apps/details?id=com.smartattend.app">
                        </div>

                        <div style="margin-top:14px">
                            <label class="plabel">Release Notes &amp; What's New</label>
                            <textarea class="pfield" id="ver_notes" rows="4" placeholder="List new features, improvements, and bug fixes...">${esc(cfg.release_notes || '')}</textarea>
                        </div>

                        <div class="actions" style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end">
                            <button class="btn primary" id="ver_save_btn" onclick="saveAppVersion()">
                                Save &amp; Push Update
                            </button>
                        </div>
                    </div>

                    <div class="org" style="border-left:4px solid var(--accent);background:rgba(124,58,237,0.02)">
                        <h4 style="margin:0 0 8px;display:flex;align-items:center;gap:8px">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            How Updates Work (Soft vs Forced)
                        </h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px;color:var(--muted);line-height:1.6;margin-top:10px">
                            <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid rgba(32,15,53,0.08)">
                                <div style="font-weight:700;color:var(--ink);margin-bottom:4px">🟢 Soft Update (Smooth / Optional)</div>
                                <div>Occurs when user version &lt; <strong>Latest Version</strong> and &ge; <strong>Minimum Required Version</strong> (and Force Update is OFF).</div>
                                <div style="margin-top:4px">The app displays an update dialog with <em>"Update Now"</em> and <em>"Later"</em> buttons. If dismissed, user is not prompted again for that version.</div>
                            </div>
                            <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid rgba(32,15,53,0.08)">
                                <div style="font-weight:700;color:var(--ink);margin-bottom:4px">🔴 Forced Update (Mandatory)</div>
                                <div>Occurs when user version &lt; <strong>Minimum Required Version</strong> OR when <strong>Force Update</strong> is turned ON.</div>
                                <div style="margin-top:4px">The dialog has <strong>no close or later button</strong>, hardware back button is blocked, and user cannot use the app until updated.</div>
                            </div>
                        </div>
                        <div style="font-size:12.5px;color:var(--muted);margin-top:12px">
                            <strong>Release Workflow:</strong> 1. Run <code>npx eas-cli build --platform android --profile preview</code> &rarr; 2. Host the APK or copy EAS build URL &rarr; 3. Enter new version in "Latest Available Version" and APK URL in "Direct APK Download Link" &rarr; 4. Click "Save &amp; Push Update".
                        </div>
                    </div>
                `;
            } catch (e) {
                box.innerHTML = `<div class="org" style="color:var(--red)">Failed to load version configuration: ${esc(e.message)}</div>`;
            }
        }

        async function saveAppVersion() {
            const latest = document.getElementById('ver_latest')?.value?.trim();
            const min = document.getElementById('ver_min')?.value?.trim();
            const force = document.getElementById('ver_force')?.checked || false;
            const title = document.getElementById('ver_title')?.value?.trim();
            const apk = document.getElementById('ver_apk')?.value?.trim();
            const store = document.getElementById('ver_store')?.value?.trim();
            const notes = document.getElementById('ver_notes')?.value?.trim();

            if (!latest || !min || !title) {
                toast('Please provide Latest Version, Minimum Version, and Title.', false);
                return;
            }

            const ok = await customConfirm(
                `Push version ${latest} update for ${currentVersionPlatform.toUpperCase()}? ${force ? 'This will FORCE all users to update immediately.' : 'This will offer an optional/soft update to users.'}`,
                {
                    title: 'Confirm App Update Rollout',
                    tone: force ? 'warning' : 'info',
                    confirmText: 'Push Update',
                }
            );
            if (!ok) return;

            const btn = document.getElementById('ver_save_btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Saving...'; }

            try {
                const res = await api('/portal/api/app-version', {
                    method: 'POST',
                    body: JSON.stringify({
                        platform: currentVersionPlatform,
                        latest_version: latest,
                        min_version: min,
                        force_update: force,
                        title: title,
                        apk_url: apk || null,
                        store_url: store || null,
                        release_notes: notes || null,
                    }),
                });
                toast(res.message || 'App update settings saved successfully.', true);
                await loadAppVersion(currentVersionPlatform);
            } catch (e) {
                toast(e.message || 'Failed to update app version.', false);
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Save & Push Update'; }
            }
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
                else if (mode === 'sms') await loadSms();
                else if (mode === 'updates') await loadAppVersion();
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
            if (initialHash && ['orgs', 'subs', 'packages', 'promos', 'notifications', 'sms', 'updates'].includes(initialHash)) {
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
