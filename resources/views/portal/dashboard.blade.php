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
        .stat-btn { display:block; width:100%; text-align:left; font:inherit; cursor:pointer; transition:opacity .15s; }
        .stat-btn:hover { opacity:.75; }
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
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
        .grid span { display:block; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        .grid b { font-size:13px; word-break:break-word; }
        .branch { background:rgba(32,15,53,.04); border:1px solid rgba(32,15,53,.1); border-radius:10px; padding:10px 12px; font-size:13px; font-weight:800; margin-bottom:8px; }
        .branch span { font-weight:400; color:var(--muted); font-size:12px; }
        table.emps { width:100%; border-collapse:collapse; font-size:13px; }
        table.emps th { text-align:left; background:rgba(32,15,53,.06); padding:8px 10px; color:var(--ink); font-size:11px; text-transform:uppercase; }
        table.emps td { padding:8px 10px; border-bottom:1px solid rgba(32,15,53,.08); }
        nav { display:flex; gap:8px; margin-bottom:20px; }
        .nav { background:#fff; border:1px solid rgba(32,15,53,.2); border-radius:12px; padding:10px 18px; font-weight:800; font-size:14px; cursor:pointer; }
        .nav.active { background:var(--ink); color:var(--bg); border-color:var(--ink); }
        select { padding:8px 10px; border-radius:8px; border:1px solid rgba(32,15,53,.2); background:#fff; }
        .pfield { width:100%; padding:9px 11px; border-radius:8px; border:1px solid rgba(32,15,53,.2); font-size:13px; margin-top:4px; background:#fff; }
        .plabel { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--ink); margin-top:10px; }
        .switch { display:inline-flex; align-items:center; gap:8px; font-weight:800; font-size:13px; cursor:pointer; margin-top:10px; }
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
        <nav id="nav">
            <button class="nav active" data-mode="orgs">Organizations</button>
            <button class="nav" data-mode="subs">Subscriptions</button>
            <button class="nav" data-mode="packages">Packages</button>
        </nav>

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
            mode = 'orgs';
            current = status;
            document.querySelectorAll('.nav').forEach(n => n.classList.toggle('active', n.dataset.mode === 'orgs'));
            document.getElementById('orgArea').style.display = '';
            document.getElementById('subsArea').style.display = 'none';
            document.getElementById('packagesArea').style.display = 'none';
            setFilterButton();
            loadOrgs().catch(() => {});
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

        document.getElementById('nav').addEventListener('click', (e) => {
            const btn = e.target.closest('.nav');
            if (!btn) return;
            mode = btn.dataset.mode;
            document.querySelectorAll('.nav').forEach(n => n.classList.toggle('active', n.dataset.mode === mode));
            const orgArea = document.getElementById('orgArea');
            const subsArea = document.getElementById('subsArea');
            if (mode === 'orgs') { orgArea.style.display = ''; subsArea.style.display = 'none'; document.getElementById('packagesArea').style.display = 'none'; loadOrgs().catch(() => {}); }
            else if (mode === 'subs') { orgArea.style.display = 'none'; subsArea.style.display = ''; document.getElementById('packagesArea').style.display = 'none'; loadSubs().catch(() => {}); }
            else { orgArea.style.display = 'none'; subsArea.style.display = 'none'; document.getElementById('packagesArea').style.display = ''; loadPackages().catch(() => {}); }
        });

        let mode = 'orgs';

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
