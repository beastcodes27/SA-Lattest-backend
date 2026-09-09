<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartAttend Admin &middot; Dashboard</title>
    <style>
        :root { --bg:#D7FFE0; --ink:#200F35; --text:#1A1231; --muted:#55576B; --green:#1F7A43; --amber:#B8860B; --red:#C23030; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; background:var(--bg); color:var(--text); }
        header { display:flex; align-items:center; justify-content:space-between; padding:18px 28px; }
        .brand { font-size:20px; font-weight:800; color:var(--ink); }
        .who { color:var(--muted); font-size:13px; }
        .logout { color:var(--ink); font-weight:700; text-decoration:none; }
        .wrap { max-width:960px; margin:0 auto; padding:0 24px 60px; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:24px; }
        .stat { background:#fff; border:1px solid rgba(32,15,53,.1); border-radius:14px; padding:14px; }
        .stat b { font-size:26px; color:var(--ink); display:block; }
        .stat span { color:var(--muted); font-size:12px; }
        .filters { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .filter { border:1px solid rgba(32,15,53,.2); background:#fff; border-radius:999px; padding:8px 16px; cursor:pointer; font-weight:600; font-size:13px; }
        .filter.active { background:var(--ink); color:var(--bg); border-color:var(--ink); }
        .org { background:#fff; border:1px solid rgba(32,15,53,.1); border-radius:16px; padding:16px 18px; margin-bottom:12px; }
        .org h3 { margin:0 0 2px; font-size:16px; }
        .org .contact { color:var(--muted); font-size:12px; margin-bottom:10px; }
        .meta { display:flex; gap:14px; font-size:12px; color:var(--muted); flex-wrap:wrap; }
        .pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:800; }
        .actions { display:flex; gap:8px; margin-top:14px; flex-wrap:wrap; }
        .btn { border:0; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:800; cursor:pointer; }
        .btn.primary { background:var(--ink); color:var(--bg); }
        .btn.danger { background:rgba(194,48,48,.1); color:var(--red); }
        .btn.ghost { background:rgba(32,15,53,.05); color:var(--ink); }
        .btn[disabled]{ opacity:.5; cursor:default; }
        .msg { position:fixed; left:50%; transform:translateX(-50%); top:18px; border-radius:12px; padding:12px 18px; font-weight:700; color:#fff; box-shadow:0 10px 30px rgba(0,0,0,.2); display:none; }
        .msg.ok { background:var(--green); }
        .msg.err { background:var(--red); }
    </style>
</head>
<body>
    <header>
        <div class="brand">SmartAttend &middot; Admin</div>
        <div>
            <span class="who">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('portal.logout') }}" style="display:inline; margin-left:14px;">
                @csrf
                <button class="logout" type="submit" style="background:none;border:0;cursor:pointer;">Sign out</button>
            </form>
        </div>
    </header>

    <div class="wrap">
        <div class="stats" id="stats"></div>
        <div class="filters" id="filters">
            <button class="filter active" data-status="">All</button>
            <button class="filter" data-status="pending">Pending</button>
            <button class="filter" data-status="active">Active</button>
            <button class="filter" data-status="suspended">Suspended</button>
        </div>
        <div id="orgs"></div>
    </div>

    <div class="msg" id="msg"></div>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let current = '';
        const $msg = document.getElementById('msg');
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const cap = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

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
            const tiles = [
                ['Pending', stats.pending], ['Active', stats.active], ['Suspended', stats.suspended],
                ['Organizations', stats.organizations_total], ['Employees', stats.employees],
                ['Branches', stats.branches], ['Face check-ins today', stats.today_checkins],
            ];
            document.getElementById('stats').innerHTML = tiles.map(([label, value]) => `<div class="stat"><b>${value}</b><span>${label}</span></div>`).join('');
        }

        async function loadOrgs() {
            const q = current ? '?status=' + current : '';
            const { organizations } = await api('/portal/api/organizations' + q);
            const box = document.getElementById('orgs');
            if (!organizations.length) { box.innerHTML = '<p style="color:var(--muted)">No organizations in this view.</p>'; return; }
            box.innerHTML = organizations.map(o => {
                const status = { pending: ['Pending review', 'var(--amber)'], active: ['Active', 'var(--green)'], suspended: ['Suspended', 'var(--red)'] }[o.status] || ['Pending', 'var(--amber)'];
                const buttons = o.status === 'pending'
                    ? `<button class="btn primary" onclick="act(${o.id},'approve')">Approve</button><button class="btn danger" onclick="act(${o.id},'reject')">Reject</button>`
                    : o.status === 'active'
                        ? `<button class="btn danger" onclick="act(${o.id},'suspend')">Suspend</button>`
                        : `<button class="btn primary" onclick="act(${o.id},'reactivate')">Reactivate</button>`;
                const trial = o.on_trial ? `<div style="color:var(--amber);font-size:12px;font-weight:600;margin-top:8px">Trial ends ${fmtDate(o.trial_ends_at)} &middot; ${o.trial_days_left}d left</div>` : '';
                return `<div class="org">
                    <h3>${esc(o.name)}</h3>
                    <div class="contact">${esc(o.contact_email || o.contact_phone || '—')}</div>
                    <div class="meta">
                        <span><span class="pill" style="color:${status[1]};background:${status[1]}1A">${status[0]}</span></span>
                        <span>Plan: <b>${cap(o.plan)}</b></span>
                        <span>Employees ${o.employees_count}/${o.employees_limit === null ? '∞' : o.employees_limit}</span>
                        <span>Branches ${o.branches_count}/${o.branches_limit === null ? '∞' : o.branches_limit}</span>
                        <span>Prefix ${esc(o.employee_id_prefix || '—')}</span>
                    </div>
                    ${trial}
                    <div class="actions">${buttons}</div>
                </div>`;
            }).join('');
        }

        function fmtDate(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
        }

        async function act(id, type) {
            const names = {
                approve: ['Approve this organization? It starts a 30-day free trial.', () => api(`/portal/api/organizations/${id}/approve`, { method: 'POST', body: JSON.stringify({ days: 30 }) })],
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

        document.getElementById('filters').addEventListener('click', (e) => {
            const btn = e.target.closest('.filter');
            if (!btn) return;
            document.querySelectorAll('.filter').forEach(f => f.classList.remove('active'));
            btn.classList.add('active');
            current = btn.dataset.status;
            loadOrgs().catch((e) => e.message !== 'expired' && toast(e.message, false));
        });

        (async () => {
            try {
                await Promise.all([loadStats(), loadOrgs()]);
            } catch (e) { /* redirect handled */ }
        })();
    </script>
</body>
</html>
