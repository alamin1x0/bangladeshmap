<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? (\App\Support\WebSettings::get('site_name').' Admin') }}</title>
    <meta name="robots" content="noindex, nofollow">
@include('partials.brand-head')
    <script nonce="{{ Vite::cspNonce() }}">
        (() => {
            const read = (key) => { try { return localStorage.getItem(key); } catch (e) { return null; } };
            if (read('geonexa-theme') === 'dark') document.documentElement.dataset.theme = 'dark';
            if (read('geonexa-sidebar') === 'collapsed') document.documentElement.dataset.sidebar = 'collapsed';
        })();
    </script>
    <style>
        :root {
            --ink:#172033; --muted:#758198; --primary:#2563eb; --primary-soft:#eaf1ff;
            --bg:#f6f8fc; --surface:#fff; --surface-2:#f8faff; --line:#e6ebf2;
            --sidebar:#101a31; --sidebar-text:#9aa8c3; --sidebar-hover:#1c2b4b; --sidebar-active:#24375f;
            --shadow:0 8px 24px rgba(27,45,79,.05);
            --sidebar-w:216px; --sidebar-mini:64px;
        }
        [data-theme="dark"] {
            --ink:#edf3ff; --muted:#91a0b9; --primary:#73a3ff; --primary-soft:#1d3158;
            --bg:#0d1425; --surface:#131e34; --surface-2:#192640; --line:#253350;
            --sidebar:#0a1020; --sidebar-text:#94a3bf; --sidebar-hover:#16223d; --sidebar-active:#1f3157;
            --shadow:0 8px 24px rgba(0,0,0,.18);
        }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:var(--bg); font:13px/1.5 Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif; transition:background .2s,color .2s; }
        button, input, select, textarea { font:inherit; }
        :focus-visible { outline:2px solid var(--primary); outline-offset:2px; }

        /* ---------- Shell & sidebar ---------- */
        .app-shell { display:flex; min-height:100vh; }
        .sidebar { position:sticky; top:0; height:100vh; width:var(--sidebar-w); flex:0 0 var(--sidebar-w); display:flex; flex-direction:column; color:var(--sidebar-text); background:var(--sidebar); padding:14px 10px 12px; z-index:30; overflow-x:hidden; overflow-y:auto; transition:width .22s ease, flex-basis .22s ease, transform .25s ease; scrollbar-width:thin; }
        .sidebar-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:0 6px; margin-bottom:16px; min-height:32px; }
        .brand { display:flex; gap:10px; align-items:center; color:#fff; font-size:16px; font-weight:800; letter-spacing:-.4px; text-decoration:none; min-width:0; }
        .brand-icon { padding:0; background:none !important; object-fit:cover; }
        .brand-logo-wrap { display:flex; align-items:center; min-width:0; }
        .brand-logo { max-height:28px; max-width:140px; width:auto; object-fit:contain; display:block; border-radius:4px; }
        .brand-mark { display:grid; place-items:center; width:30px; height:30px; flex:0 0 30px; background:linear-gradient(135deg,#62a0ff,#2563eb); border-radius:10px; color:white; box-shadow:0 6px 14px #2563eb55; }
        .sidebar-close { display:none; place-items:center; width:32px; height:32px; border:0; border-radius:9px; background:#ffffff12; color:#dce7ff; cursor:pointer; }
        .sidebar-close svg { width:16px; height:16px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:2px; flex:1; }
        .nav-label { color:#5f7090; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1.1px; margin:12px 11px 5px; white-space:nowrap; }
        .nav-label:first-child { margin-top:0; }
        .nav-link { position:relative; display:flex; align-items:center; gap:10px; padding:7px 11px; border-radius:8px; color:var(--sidebar-text); text-decoration:none; font-size:12.5px; white-space:nowrap; transition:background .15s, color .15s; }
        .nav-link svg { width:16px; height:16px; flex:0 0 16px; opacity:.9; }
        .nav-link:hover { background:var(--sidebar-hover); color:#fff; }
        .nav-link.active { background:var(--sidebar-active); color:#fff; font-weight:600; }
        .nav-link.active::before { content:""; position:absolute; left:-10px; top:7px; bottom:7px; width:3px; border-radius:0 3px 3px 0; background:#62a0ff; }
        .sidebar-bottom { border-top:1px solid #ffffff10; margin-top:14px; padding:12px 4px 0; display:flex; align-items:center; gap:8px; }
        .profile-mini { display:flex; gap:9px; align-items:center; color:#dce7ff; font-size:13px; min-width:0; flex:1; }
        .profile-mini > div:last-child { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .avatar { display:grid; place-items:center; width:28px; height:28px; flex:0 0 28px; border-radius:50%; color:#fff; background:#3a67bd; font-weight:700; font-size:12px; }
        .profile-mini small { display:block; color:#7182a2; font-size:11px; }
        .collapse-btn { display:grid; place-items:center; width:32px; height:32px; flex:0 0 32px; border:0; border-radius:9px; background:#ffffff0d; color:#b9c6df; cursor:pointer; transition:background .15s, transform .22s; }
        .collapse-btn:hover { background:#ffffff1c; color:#fff; }
        .collapse-btn svg { width:16px; height:16px; transition:transform .22s; }

        /* Collapsed (desktop): icon rail with hover labels */
        @media (min-width:861px) {
            [data-sidebar="collapsed"] .sidebar { width:var(--sidebar-mini); flex-basis:var(--sidebar-mini); overflow:visible; }
            [data-sidebar="collapsed"] .nav-text, [data-sidebar="collapsed"] .profile-mini > div:last-child { display:none; }
            [data-sidebar="collapsed"] .sidebar-head { justify-content:center; padding:0; }
            [data-sidebar="collapsed"] .nav-label { height:1px; margin:12px 10px; padding:0; overflow:hidden; color:transparent; background:#ffffff14; }
            [data-sidebar="collapsed"] .nav-link { justify-content:center; padding:9px 0; }
            [data-sidebar="collapsed"] .nav-link::after { content:attr(data-label); position:absolute; left:calc(100% + 12px); top:50%; transform:translateY(-50%) translateX(-4px); padding:6px 10px; border-radius:8px; background:#101a31; color:#fff; font-size:12px; font-weight:600; white-space:nowrap; box-shadow:0 8px 20px #0004; opacity:0; pointer-events:none; transition:opacity .12s, transform .12s; }
            [data-sidebar="collapsed"] .nav-link:hover::after, [data-sidebar="collapsed"] .nav-link:focus-visible::after { opacity:1; transform:translateY(-50%); }
            [data-sidebar="collapsed"] .sidebar-bottom { flex-direction:column; padding:12px 0 0; }
            [data-sidebar="collapsed"] .profile-mini { flex:none; }
            [data-sidebar="collapsed"] .collapse-btn svg { transform:rotate(180deg); }
        }

        /* ---------- Main ---------- */
        .main { width:100%; min-width:0; padding:16px clamp(14px,2.4vw,28px) 26px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
        .eyebrow { color:var(--primary); font-size:10.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; margin:0 0 3px; }
        h1 { font-size:19px; line-height:1.2; margin:0; letter-spacing:-.5px; } .sub { color:var(--muted); margin:2px 0 0; font-size:12px; }
        .top-actions { display:flex; align-items:center; gap:8px; }
        .icon-btn, .logout { border:1px solid var(--line); background:var(--surface); color:var(--ink); border-radius:9px; cursor:pointer; transition:.2s; }
        .icon-btn { display:grid; place-items:center; width:32px; height:32px; flex:0 0 32px; } .icon-btn:hover, .logout:hover { border-color:var(--primary); color:var(--primary); }
        .icon-btn svg { width:15px; height:15px; }
        .menu-btn { display:grid; }
        .logout { padding:6px 11px; font-weight:600; font-size:12px; white-space:nowrap; }
        .cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
        .card, .table-card { background:var(--surface); border:1px solid var(--line); border-radius:14px; box-shadow:var(--shadow); }
        .card { position:relative; overflow:hidden; padding:14px 16px; } .card::after { content:""; position:absolute; width:75px; height:75px; right:-22px; top:-22px; border-radius:50%; background:var(--primary-soft); }
        .card-icon { display:grid; place-items:center; width:34px; height:34px; margin-bottom:14px; color:var(--primary); background:var(--primary-soft); border-radius:10px; }
        .card-icon svg { width:18px; height:18px; } .card-label { color:var(--muted); font-size:13px; } .card-value { font-size:22px; font-weight:800; margin-top:4px; letter-spacing:-.8px; }
        .table-head { display:flex; justify-content:space-between; align-items:center; gap:15px; padding:12px 16px; border-bottom:1px solid var(--line); }
        .table-head h2 { font-size:14px; margin:0; } .table-wrap { overflow:auto; } table { width:100%; border-collapse:collapse; }
        th, td { padding:9px 14px; text-align:left; border-bottom:1px solid var(--line); white-space:nowrap; } th { color:var(--muted); font-size:10px; letter-spacing:.8px; font-weight:700; }
        tbody tr:last-child td { border-bottom:0; } tbody tr:hover { background:var(--surface-2); } td { font-size:12.5px; }
        .person { display:flex; align-items:center; gap:9px; font-weight:600; } .tiny-avatar { display:grid; place-items:center; width:27px; height:27px; color:var(--primary); background:var(--primary-soft); border-radius:8px; font-size:11px; }
        .badge { background:#e9f8ef; color:#168346; border-radius:20px; padding:4px 9px; font-size:11px; font-weight:600; } [data-theme="dark"] .badge { background:#173827; color:#69d998; }

        /* ---------- Forms (shared by all add / edit screens) ---------- */
        .page-back { display:inline-flex; align-items:center; gap:5px; color:var(--muted); font-size:12px; font-weight:600; text-decoration:none; margin-bottom:3px; }
        .page-back:hover { color:var(--primary); } .page-back svg { width:14px; height:14px; }
        .form-layout { display:grid; grid-template-columns:minmax(0,1fr) 290px; gap:16px; align-items:start; max-width:1120px; }
        .form-card { background:var(--surface); border:1px solid var(--line); border-radius:14px; box-shadow:var(--shadow); min-width:0; }
        .form-section { padding:14px 16px; } .form-section + .form-section { border-top:1px solid var(--line); }
        .form-section-head { display:flex; gap:11px; align-items:flex-start; margin-bottom:12px; }
        .step { display:grid; place-items:center; width:26px; height:26px; flex:0 0 26px; border-radius:8px; background:var(--primary-soft); color:var(--primary); font-weight:800; font-size:12px; }
        .form-section-head h2 { font-size:13.5px; margin:0; line-height:1.3; } .form-section-head p { margin:2px 0 0; color:var(--muted); font-size:12px; }
        .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px 14px; } .span-2 { grid-column:1 / -1; }
        .field { display:grid; gap:6px; min-width:0; align-content:start; }
        .field-label { display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; color:var(--ink); }
        .req { color:#e05252; } .opt { color:var(--muted); font-weight:500; font-size:11.5px; }
        .input { width:100%; padding:8px 11px; border:1px solid var(--line); border-radius:9px; background-color:var(--surface); color:var(--ink); font-size:13px; transition:border-color .15s, box-shadow .15s; }
        .input::placeholder { color:#9aa6ba; }
        .input:hover { border-color:#c6d0e0; } [data-theme="dark"] .input:hover { border-color:#3a4b6e; }
        .input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px #2563eb22; }
        .input[aria-invalid="true"] { border-color:#e05252; box-shadow:0 0 0 3px #e0525218; }
        .input:disabled { opacity:.55; cursor:not-allowed; background-color:var(--surface-2); }
        textarea.input { resize:vertical; min-height:92px; line-height:1.5; }
        select.input { appearance:none; padding-right:34px; cursor:pointer; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238592a8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 11px center; background-size:15px; }
        .hint { color:var(--muted); font-size:11.5px; line-height:1.4; } .field-error { color:#e05252; font-size:11.5px; font-weight:600; }
        .field-foot { display:flex; justify-content:space-between; gap:10px; }
        .input-group { position:relative; } .input-group .input { padding-right:76px; }
        .input-addons { position:absolute; right:5px; top:50%; transform:translateY(-50%); display:flex; gap:2px; }
        .input-addon { display:grid; place-items:center; width:32px; height:32px; border:0; border-radius:7px; background:transparent; color:var(--muted); cursor:pointer; }
        .input-addon:hover { background:var(--surface-2); color:var(--primary); } .input-addon svg { width:16px; height:16px; }
        .meter { display:grid; grid-template-columns:repeat(4,1fr); gap:4px; } .meter i { height:4px; border-radius:2px; background:var(--surface-2); transition:background .2s; }
        .meter[data-score="1"] i:nth-child(-n+1) { background:#e05252; } .meter[data-score="2"] i:nth-child(-n+2) { background:#fab219; }
        .meter[data-score="3"] i:nth-child(-n+3) { background:#3b9b4a; } .meter[data-score="4"] i { background:#0ca30c; }
        .segmented { display:grid; grid-auto-flow:column; grid-auto-columns:1fr; gap:6px; }
        .segmented label { position:relative; display:block; }
        .segmented input { position:absolute; opacity:0; pointer-events:none; }
        .segmented span { display:flex; align-items:center; justify-content:center; gap:7px; padding:7px 8px; border:1px solid var(--line); border-radius:8px; font-size:12px; font-weight:600; color:var(--muted); cursor:pointer; transition:.15s; white-space:nowrap; }
        .segmented span:hover { border-color:#c6d0e0; color:var(--ink); }
        .segmented input:checked + span { border-color:var(--primary); background:var(--primary-soft); color:var(--primary); }
        .segmented input:focus-visible + span { outline:2px solid var(--primary); outline-offset:2px; }
        .dot { width:8px; height:8px; flex:0 0 8px; border-radius:50%; } .dot.active { background:#0ca30c; } .dot.inactive { background:#fab219; } .dot.blocked { background:#d03b3b; }
        .alert-error { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-radius:11px; background:#fdecec; color:#b42318; font-size:13px; margin-bottom:14px; max-width:1120px; }
        .alert-error svg { width:18px; height:18px; flex:0 0 18px; margin-top:1px; } .alert-error ul { margin:3px 0 0; padding-left:17px; }
        [data-theme="dark"] .alert-error { background:#3a1b1f; color:#ff9a9a; }
        .form-actions { position:sticky; bottom:0; z-index:5; display:flex; justify-content:flex-end; align-items:center; gap:8px; padding:10px 16px; border-top:1px solid var(--line); background:var(--surface); border-radius:0 0 14px 14px; }
        .form-actions .hint { margin-right:auto; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:7px 14px; border-radius:8px; font-weight:700; font-size:12.5px; text-decoration:none; cursor:pointer; border:1px solid var(--line); background:var(--surface); color:var(--ink); transition:.15s; white-space:nowrap; }
        .btn:hover { border-color:var(--primary); color:var(--primary); } .btn svg { width:15px; height:15px; }
        .btn-primary { background:var(--primary); border-color:var(--primary); color:#fff; box-shadow:0 6px 14px #2563eb30; } .btn-primary:hover { color:#fff; filter:brightness(1.08); }
        .btn[disabled] { opacity:.7; cursor:progress; }
        .side-stack { position:sticky; top:20px; display:grid; gap:12px; }
        .side-card { padding:16px; } .side-card h3 { margin:0 0 10px; font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }
        .tips { margin:0; padding:0; list-style:none; display:grid; gap:9px; font-size:12.5px; color:var(--muted); }
        .tips li { display:flex; gap:8px; } .tips svg { width:15px; height:15px; flex:0 0 15px; color:var(--primary); margin-top:2px; }
        .dropzone { position:relative; display:grid; place-items:center; gap:3px; text-align:center; padding:22px 16px; border:1.5px dashed var(--line); border-radius:12px; background:var(--surface-2); color:var(--muted); font-size:12.5px; transition:.15s; }
        .dropzone:hover, .dropzone.drag { border-color:var(--primary); background:var(--primary-soft); }
        .dropzone input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
        .dropzone strong { color:var(--primary); } .dropzone svg { width:26px; height:26px; color:var(--primary); margin-bottom:4px; }
        .file-preview { display:flex; align-items:center; gap:12px; padding:10px; border:1px solid var(--line); border-radius:12px; }
        .file-preview img { width:56px; height:56px; border-radius:9px; object-fit:cover; flex:0 0 56px; background:var(--surface-2); }
        .file-preview div { min-width:0; flex:1; font-size:12.5px; } .file-preview strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; } .file-preview span { color:var(--muted); font-size:11.5px; }
        [hidden] { display:none !important; }
        @media (max-width:980px) {
            .form-layout { grid-template-columns:minmax(0,1fr); }
            .side-stack { position:static; } .side-stack .tips-card { display:none; }
        }
        @media (max-width:650px) {
            .form-grid { grid-template-columns:minmax(0,1fr); }
            .form-section { padding:16px; } .form-actions { padding:10px 16px; box-shadow:0 -8px 20px #0000000d; }
            .form-actions .hint { display:none; } .form-actions .btn { flex:1; }
            .segmented span { padding:9px 4px; font-size:12px; }
        }

        /* ---------- Confirm dialog (delete etc.) ---------- */
        .confirm-dialog { width:100%; max-width:min(400px, calc(100vw - 32px)); padding:0; border:0; background:transparent; color:var(--ink); overflow:visible; }
        .confirm-dialog::backdrop { background:rgba(7,17,39,.55); backdrop-filter:blur(3px); }
        .confirm-dialog[open] .confirm-box { animation:confirm-pop .18s ease-out; }
        @keyframes confirm-pop { from { opacity:0; transform:translateY(10px) scale(.97); } }
        .confirm-box { position:relative; background:var(--surface); border:1px solid var(--line); border-radius:16px; padding:24px 22px 18px; box-shadow:0 30px 80px rgba(0,0,0,.3); text-align:center; }
        .confirm-close { position:absolute; top:10px; right:10px; display:grid; place-items:center; width:30px; height:30px; border:0; border-radius:8px; background:transparent; color:var(--muted); cursor:pointer; }
        .confirm-close:hover { background:var(--surface-2); color:var(--ink); } .confirm-close svg { width:16px; height:16px; }
        .confirm-icon { display:grid; place-items:center; width:50px; height:50px; margin:0 auto 14px; border-radius:50%; background:#fdecec; color:#d03b3b; box-shadow:0 0 0 7px #fdecec80; }
        .confirm-icon svg { width:23px; height:23px; }
        [data-theme="dark"] .confirm-icon { background:#3a1b1f; color:#ff7b7b; box-shadow:0 0 0 7px #3a1b1f80; }
        .confirm-dialog.primary .confirm-icon { background:var(--primary-soft); color:var(--primary); box-shadow:0 0 0 7px color-mix(in srgb, var(--primary-soft) 55%, transparent); }
        .confirm-dialog .icon-send { display:none; } .confirm-dialog.primary .icon-send { display:block; } .confirm-dialog.primary .icon-trash { display:none; }
        .confirm-dialog.primary .btn-danger { background:var(--primary); border-color:var(--primary); box-shadow:0 6px 14px rgba(37,99,235,.28); }
        .confirm-box h2 { margin:0; font-size:16px; letter-spacing:-.2px; }
        .confirm-box p { margin:6px 0 0; color:var(--muted); font-size:12.5px; line-height:1.55; }
        .confirm-item { display:flex; align-items:center; justify-content:center; gap:8px; margin-top:14px; padding:9px 12px; border-radius:10px; background:var(--surface-2); border:1px solid var(--line); font-size:12.5px; font-weight:700; word-break:break-word; }
        .confirm-item svg { width:15px; height:15px; flex:0 0 15px; color:var(--muted); }
        .confirm-actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:18px; }
        .confirm-actions .btn { padding:9px 14px; }
        .btn-danger { background:#d03b3b; border-color:#d03b3b; color:#fff; box-shadow:0 6px 14px rgba(208,59,59,.28); }
        .btn-danger:hover { background:#b92f2f; border-color:#b92f2f; color:#fff; }
        .spinner { width:14px; height:14px; border:2px solid rgba(255,255,255,.45); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        @media (max-width:650px) {
            .confirm-dialog { max-width:100%; margin:auto 0 0; }
            .confirm-box { border-radius:18px 18px 0 0; border-bottom:0; padding:22px 18px calc(16px + env(safe-area-inset-bottom)); }
            .confirm-box::before { content:""; display:block; width:36px; height:4px; margin:-10px auto 14px; border-radius:2px; background:var(--line); }
            .confirm-dialog[open] .confirm-box { animation:confirm-sheet .22s ease-out; }
            @keyframes confirm-sheet { from { transform:translateY(100%); } }
            .confirm-actions .btn { padding:11px 14px; font-size:14px; }
        }
        @media (prefers-reduced-motion: reduce) { .confirm-dialog[open] .confirm-box { animation:none; } }

        /* ---------- Mobile / tablet: slide-in drawer ---------- */
        .mobile-overlay { position:fixed; inset:0; z-index:25; background:#07112799; backdrop-filter:blur(2px); opacity:0; visibility:hidden; transition:opacity .25s, visibility .25s; }
        @media (max-width:860px) {
            .sidebar { position:fixed; inset:0 auto 0 0; width:min(280px,84vw); transform:translateX(-102%); box-shadow:none; }
            .sidebar.open { transform:translateX(0); box-shadow:20px 0 50px #0006; }
            .sidebar-close { display:grid; }
            .collapse-btn { display:none; }
            .mobile-overlay.open { opacity:1; visibility:visible; }
            body.drawer-open { overflow:hidden; }
        }
        @media (max-width:650px) {
            .main { padding:14px 12px 24px; }
            .topbar { align-items:flex-start; margin-bottom:14px; } h1 { font-size:18px; }
            .cards { grid-template-columns:1fr; gap:10px; } .table-head { align-items:stretch; flex-direction:column; padding:13px; } th,td { padding:11px 14px; }

            /* Search / filter bars: full width, wrap instead of overflowing */
            .table-head form, .table-head form > div { width:100%; flex-wrap:wrap; }
            .table-head input[type="search"], .table-head input[type="text"], .table-head select { flex:1 1 150px; width:auto !important; min-width:0; }
            .table-head form button, .table-head form .logout { flex:0 0 auto; }

            /* 16px text in fields stops iOS Safari from zooming in on focus */
            input:not([type="checkbox"]):not([type="radio"]):not([type="file"]), select, textarea, .input { font-size:16px !important; }

            /* Tables become one card per row (labels are added automatically from the header) */
            .table-wrap.stacked { overflow:visible; }
            .table-wrap.stacked table, .table-wrap.stacked tbody, .table-wrap.stacked tr, .table-wrap.stacked td { display:block; width:100%; }
            .table-wrap.stacked thead { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
            .table-wrap.stacked tbody { display:grid; gap:8px; padding:10px; }
            .table-wrap.stacked tr { border:1px solid var(--line); border-radius:12px; background:var(--surface); padding:4px 0; }
            .table-wrap.stacked tbody tr:hover { background:var(--surface); }
            .table-wrap.stacked td { display:flex; align-items:center; justify-content:space-between; gap:12px; min-height:34px; padding:6px 12px; border:0; white-space:normal; text-align:right; word-break:break-word; }
            .table-wrap.stacked td + td { border-top:1px dashed var(--line); }
            .table-wrap.stacked td::before { content:attr(data-label); flex:0 0 auto; max-width:45%; color:var(--muted); font-size:10px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; text-align:left; }
            .table-wrap.stacked td:not([data-label])::before, .table-wrap.stacked td[data-label=""]::before { content:none; }
            .table-wrap.stacked td[data-label="#"] { display:none; }
            .table-wrap.stacked td[data-label="#"] + td { border-top:0; }
            .table-wrap.stacked td[colspan] { justify-content:center; text-align:center; color:var(--muted); }
            .table-wrap.stacked td:first-child:not([data-label="#"]), .table-wrap.stacked td[data-label="#"] + td { padding-top:9px; padding-bottom:9px; }
            .table-wrap.stacked td > div, .table-wrap.stacked td > form { justify-content:flex-end; }
            .table-wrap.stacked td .person { text-align:left; }
        }
        @media (prefers-reduced-motion: reduce) { .sidebar, .mobile-overlay, .nav-link::after { transition:none !important; } }
    </style>
    @stack('head')
</head>
<body>
    @php
        $navGroups = [
            'Workspace' => [
                ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>'],
                ['route' => 'deletion-logs.index', 'match' => 'deletion-logs.*', 'label' => 'Deleted Data', 'icon' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>'],
                ['route' => 'access-logs', 'match' => 'access-logs', 'label' => 'Access Logs', 'icon' => '<path d="M12 8v4l2.5 2.5"/><circle cx="12" cy="12" r="8.5"/>'],
            ],
            'Locations' => [
                ['route' => 'locations.divisions', 'match' => 'locations.divisions', 'label' => 'Divisions', 'icon' => '<path d="M9 4 3 6.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5z"/><path d="M9 4v13.5M15 6.5V20"/>'],
                ['route' => 'locations.districts', 'match' => 'locations.districts', 'label' => 'Districts', 'icon' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>'],
                ['route' => 'locations.upazilas', 'match' => 'locations.upazilas', 'label' => 'Upazilas / Thanas', 'icon' => '<rect x="4" y="4" width="6" height="6" rx="1.5"/><rect x="14" y="4" width="6" height="6" rx="1.5"/><rect x="4" y="14" width="6" height="6" rx="1.5"/><rect x="14" y="14" width="6" height="6" rx="1.5"/>'],
            ],
            'Website' => [
                ['route' => 'landing-content.edit', 'match' => 'landing-content.*', 'label' => 'Landing Page', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>'],
                ['route' => 'web-settings.edit', 'match' => 'web-settings.*', 'label' => 'Web Settings', 'icon' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>'],
                ['route' => 'landing-orders.index', 'match' => 'landing-orders.*', 'label' => 'Book Orders', 'icon' => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5z"/><path d="M20 5.5A1.5 1.5 0 0 0 18.5 4H13v16h5.5a1.5 1.5 0 0 0 1.5-1.5z"/>'],
            ],
            'People' => [
                ['route' => 'app-users.index', 'match' => 'app-users.*', 'label' => 'App Users', 'icon' => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 4.5a3 3 0 0 1 0 6M21 20a6 6 0 0 0-4-5.6"/>'],
                ['route' => 'subscription-payments.index', 'match' => 'subscription-payments.*', 'label' => 'Payment Requests', 'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>'],
                ['route' => 'notifications.index', 'match' => 'notifications.*', 'label' => 'Notifications', 'icon' => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>'],
                ['route' => 'app-settings.edit', 'match' => 'app-settings.*', 'label' => 'App Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M4 12h16M12 4v16"/>'],
                ['route' => 'settings', 'match' => 'settings', 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>'],
            ],
        ];
    @endphp
    <div class="mobile-overlay" id="mobile-overlay" aria-hidden="true"></div>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar" aria-label="Main navigation">
            <div class="sidebar-head">
                @php $__site = \App\Support\WebSettings::get('site_name'); $__logo = \App\Support\WebSettings::logoUrl(); @endphp
                <a class="brand" href="{{ route('dashboard') }}" aria-label="{{ $__site }} dashboard"><img class="brand-mark brand-icon" src="{{ \App\Support\WebSettings::icons()['192'] }}" alt="" width="30" height="30">@if($__logo)<span class="nav-text brand-logo-wrap"><img class="brand-logo" src="{{ $__logo }}" alt="{{ $__site }}"></span>@else<span class="nav-text">{{ $__site }}</span>@endif</a>
                <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            </div>
            <nav class="sidebar-nav">
                @foreach($navGroups as $group => $links)
                    <div class="nav-label">{{ $group }}</div>
                    @foreach($links as $link)
                        @php $active = request()->routeIs($link['match']); @endphp
                        <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($link['route']) }}" data-label="{{ $link['label'] }}" @if($active) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $link['icon'] !!}</svg>
                            <span class="nav-text">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>
            <div class="sidebar-bottom">
                <div class="profile-mini" title="{{ auth()->user()->username }}"><div class="avatar">{{ strtoupper(substr(auth()->user()->username, 0, 1)) }}</div><div>{{ auth()->user()->username }}<small>Administrator</small></div></div>
                <button type="button" class="collapse-btn" data-sidebar-collapse aria-label="Collapse sidebar" title="Collapse / expand menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg></button>
            </div>
        </aside>
        <main class="main">
            @yield('content')
        </main>
    </div>
    <dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-message">
        <div class="confirm-box">
            <button type="button" class="confirm-close" data-confirm-cancel aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            <div class="confirm-icon" aria-hidden="true"><svg class="icon-send" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg><svg class="icon-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg></div>
            <h2 id="confirm-title">Are you sure?</h2>
            <p id="confirm-message">This action cannot be undone.</p>
            <div class="confirm-item" id="confirm-item" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg><span></span></div>
            <div class="confirm-actions">
                <button type="button" class="btn" data-confirm-cancel>Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-ok><span data-confirm-ok-label>Delete</span></button>
            </div>
        </div>
    </dialog>
    <script nonce="{{ Vite::cspNonce() }}">
        (() => {
            const root = document.documentElement;
            const store = (key, value) => { try { localStorage.setItem(key, value); } catch (e) {} };

            // Theme toggle
            const themeButton = document.querySelector('[data-theme-toggle]');
            const updateThemeIcon = () => {
                if (!themeButton) return;
                themeButton.innerHTML = root.dataset.theme === 'dark'
                    ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>'
                    : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8Z"/></svg>';
            };
            themeButton?.addEventListener('click', () => { root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark'; store('geonexa-theme', root.dataset.theme); updateThemeIcon(); });
            updateThemeIcon();

            // Sidebar: collapsible rail on desktop, slide-in drawer on mobile
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            const menuButtons = document.querySelectorAll('[data-menu-toggle]');
            const isMobile = () => window.matchMedia('(max-width: 860px)').matches;
            const syncAria = () => {
                const expanded = isMobile() ? sidebar.classList.contains('open') : root.dataset.sidebar !== 'collapsed';
                menuButtons.forEach((b) => { b.setAttribute('aria-expanded', String(expanded)); b.setAttribute('aria-controls', 'sidebar'); });
            };
            const openDrawer = () => { sidebar.classList.add('open'); overlay.classList.add('open'); document.body.classList.add('drawer-open'); sidebar.querySelector('.nav-link')?.focus({ preventScroll: true }); syncAria(); };
            const closeDrawer = () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.classList.remove('drawer-open'); syncAria(); };
            const toggleCollapse = () => {
                const collapsed = root.dataset.sidebar === 'collapsed';
                if (collapsed) delete root.dataset.sidebar; else root.dataset.sidebar = 'collapsed';
                store('geonexa-sidebar', collapsed ? 'expanded' : 'collapsed');
                syncAria();
            };
            menuButtons.forEach((b) => b.addEventListener('click', () => isMobile() ? (sidebar.classList.contains('open') ? closeDrawer() : openDrawer()) : toggleCollapse()));
            document.querySelector('[data-sidebar-collapse]')?.addEventListener('click', toggleCollapse);
            document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeDrawer);
            overlay.addEventListener('click', closeDrawer);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar.classList.contains('open')) closeDrawer(); });
            window.matchMedia('(max-width: 860px)').addEventListener('change', () => { closeDrawer(); });
            syncAria();

            // Confirm dialog for destructive forms. Usage on a <form>:
            //   data-confirm="Body text"  [data-confirm-title="Delete user?"] [data-confirm-item="Rahim · rahim@x.com"] [data-confirm-button="Delete user"]
            const dialog = document.getElementById('confirm-dialog');
            const dTitle = document.getElementById('confirm-title');
            const dMessage = document.getElementById('confirm-message');
            const dItem = document.getElementById('confirm-item');
            const okButton = dialog.querySelector('[data-confirm-ok]');
            const okLabel = dialog.querySelector('[data-confirm-ok-label]');
            const cancelButtons = dialog.querySelectorAll('[data-confirm-cancel]');
            let pendingForm = null, lastFocus = null;

            const closeConfirm = () => {
                if (okButton.disabled) return; // already deleting
                dialog.close();
                pendingForm = null;
                lastFocus?.focus?.();
            };
            const openConfirm = (form) => {
                const { confirm: message, confirmTitle, confirmItem, confirmButton, confirmVariant, confirmBusy } = form.dataset;
                dialog.classList.toggle('primary', confirmVariant === 'primary');
                dialog.dataset.busy = confirmBusy || 'Deleting…';
                // "Delete this location set?" style messages become the title.
                const isQuestion = !confirmTitle && /\?\s*$/.test(message || '');
                dTitle.textContent = confirmTitle || (isQuestion ? message : 'Are you sure?');
                dMessage.textContent = isQuestion ? 'This action cannot be undone.' : (message || 'This action cannot be undone.');
                dItem.hidden = !confirmItem;
                dItem.querySelector('span').textContent = confirmItem || '';
                okLabel.textContent = confirmButton || 'Delete';
                okButton.disabled = false;
                pendingForm = form;
                lastFocus = document.activeElement;
                dialog.showModal();
                cancelButtons[cancelButtons.length - 1].focus(); // safe default: Enter cancels
            };

            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (!form.dataset?.confirm || form.dataset.confirmed) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                if (typeof dialog.showModal !== 'function') { // very old browsers
                    if (window.confirm(form.dataset.confirm)) { form.dataset.confirmed = '1'; form.submit(); }
                    return;
                }
                openConfirm(form);
            }, true);

            okButton.addEventListener('click', () => {
                if (!pendingForm) return;
                okButton.disabled = true;
                okLabel.innerHTML = '';
                const spinner = document.createElement('span'); spinner.className = 'spinner';
                okLabel.append(spinner, document.createTextNode(' ' + (dialog.dataset.busy || 'Deleting…')));
                okLabel.style.cssText = 'display:inline-flex;align-items:center;gap:7px';
                pendingForm.dataset.confirmed = '1';
                pendingForm.submit();
            });
            cancelButtons.forEach((b) => b.addEventListener('click', closeConfirm));
            dialog.addEventListener('cancel', (e) => { e.preventDefault(); closeConfirm(); }); // Esc
            dialog.addEventListener('click', (e) => { if (e.target === dialog) closeConfirm(); }); // backdrop
            window.addEventListener('pageshow', () => { if (dialog.open) dialog.close(); okButton.disabled = false; });

            // Mobile tables: copy each column header into its cells as data-label, so rows can render as cards.
            // Opt out with class "no-stack" on the .table-wrap.
            document.querySelectorAll('.table-wrap:not(.no-stack)').forEach((wrap) => {
                const table = wrap.querySelector('table');
                if (!table || !table.tHead) return;
                const headers = [...table.tHead.rows[0].cells].map((th) => th.textContent.trim());
                [...table.tBodies].forEach((body) => [...body.rows].forEach((row) => {
                    let col = 0;
                    [...row.cells].forEach((cell) => {
                        if (!cell.hasAttribute('colspan') && !cell.hasAttribute('data-label')) cell.setAttribute('data-label', headers[col] ?? '');
                        col += cell.colSpan || 1;
                    });
                }));
                wrap.classList.add('stacked');
            });

            // Prevent double submissions on forms marked data-submit-once.
            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (event.defaultPrevented || !form.matches('[data-submit-once]')) return;
                if (form.dataset.submitting) { event.preventDefault(); return; }
                form.dataset.submitting = '1';
                form.querySelectorAll('[type="submit"]').forEach((button) => {
                    button.disabled = true;
                    const label = button.querySelector('[data-label]');
                    if (label && button.dataset.loadingText) { label.dataset.original = label.textContent; label.textContent = button.dataset.loadingText; }
                });
            });
            window.addEventListener('pageshow', () => document.querySelectorAll('form[data-submitting]').forEach((form) => {
                delete form.dataset.submitting;
                form.querySelectorAll('[type="submit"]').forEach((button) => {
                    button.disabled = false;
                    const label = button.querySelector('[data-label]');
                    if (label?.dataset.original) label.textContent = label.dataset.original;
                });
            }));
        })();
    </script>
    @stack('scripts')
</body>
</html>
