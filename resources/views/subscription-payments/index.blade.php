@extends('layouts.app')

@php
    $filters = [
        null => ['label' => 'All', 'count' => (int) $summary->total, 'tone' => 'all'],
        'pending' => ['label' => 'Pending', 'count' => (int) $summary->pending, 'tone' => 'pending'],
        'approved' => ['label' => 'Approved', 'count' => (int) $summary->approved, 'tone' => 'approved'],
        'rejected' => ['label' => 'Rejected', 'count' => (int) $summary->rejected, 'tone' => 'rejected'],
    ];
    $chipUrl = fn ($s) => route('subscription-payments.index', array_filter(['status' => $s, 'q' => $search ?: null]));
    $taka = fn ($v) => '৳'.number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2);
    $tones = ['#2a78d6', '#1baf7a', '#6250d6', '#eb6834', '#e87ba4', '#008300'];
    $tone = fn ($name) => $tones[crc32((string) $name) % count($tones)];
    $rejectReasons = ['Transaction ID not found in bKash', 'Amount does not match the package price', 'Paid to the wrong bKash number', 'Duplicate request — already processed'];
@endphp

@push('head')
<style>
    :root { --bkash:#e2136e; --bkash-soft:#fde7f1; }
    [data-theme="dark"] { --bkash:#ff4f9a; --bkash-soft:#3a1529; }

    .pay-flash { display:flex; align-items:center; gap:9px; margin-bottom:12px; padding:10px 13px; border-radius:11px; font-size:12.5px; font-weight:600; }
    .pay-flash svg { width:16px; height:16px; flex:0 0 16px; }
    .pay-flash.ok { background:#e9f8ef; color:#146c3b; } .pay-flash.err { background:#fdecec; color:#b42318; }
    [data-theme="dark"] .pay-flash.ok { background:#123222; color:#69d998; } [data-theme="dark"] .pay-flash.err { background:#3a1b1f; color:#ff9a9a; }
    .bkash-mark { display:inline-grid; place-items:center; width:22px; height:22px; border-radius:7px; background:var(--bkash); color:#fff; font-size:12px; font-weight:900; vertical-align:middle; margin-right:2px; }

    /* Summary */
    .pay-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
    .pay-stat { display:flex; align-items:center; gap:11px; padding:12px 14px; border:1px solid var(--line); border-radius:12px; background:var(--surface); box-shadow:var(--shadow); text-decoration:none; color:inherit; transition:border-color .15s, transform .15s; min-width:0; }
    a.pay-stat:hover { border-color:var(--primary); transform:translateY(-1px); }
    .pay-stat .ic { display:grid; place-items:center; width:36px; height:36px; flex:0 0 36px; border-radius:11px; background:var(--primary-soft); color:var(--primary); }
    .pay-stat .ic svg { width:18px; height:18px; }
    .pay-stat strong { display:block; font-size:19px; letter-spacing:-.4px; line-height:1.2; font-variant-numeric:tabular-nums; }
    .pay-stat span { display:block; color:var(--muted); font-size:11.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .pay-stat.pending { border-color:color-mix(in srgb, #f0a900 45%, var(--line)); background:linear-gradient(135deg, color-mix(in srgb, #fff4dc 70%, var(--surface)), var(--surface)); }
    .pay-stat.pending .ic { background:#fff4dc; color:#b77900; }
    .pay-stat.approved .ic { background:#e6f6ea; color:#07852b; } .pay-stat.rejected .ic { background:#fdecec; color:#c62828; }
    .pay-stat.revenue .ic { background:var(--bkash-soft); color:var(--bkash); }
    [data-theme="dark"] .pay-stat.pending { background:var(--surface); } [data-theme="dark"] .pay-stat.pending .ic { background:#3a2e10; color:#ffc94f; }
    [data-theme="dark"] .pay-stat.approved .ic { background:#123222; color:#69d998; } [data-theme="dark"] .pay-stat.rejected .ic { background:#3a1b1f; color:#ff9a9a; }
    .pay-stat small { display:block; font-size:10.5px; font-weight:700; color:#b77900; } [data-theme="dark"] .pay-stat small { color:#ffc94f; }

    /* Toolbar */
    .pay-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid var(--line); flex-wrap:wrap; }
    .pchips { display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; min-width:0; }
    .pchips::-webkit-scrollbar { display:none; }
    .pchip { display:inline-flex; align-items:center; gap:6px; flex:0 0 auto; padding:5px 10px 5px 9px; border:1px solid var(--line); border-radius:999px; background:var(--surface); color:var(--muted); font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; }
    .pchip:hover { border-color:var(--primary); color:var(--ink); }
    .pchip .dot { width:7px; height:7px; border-radius:50%; background:var(--primary); }
    .pchip.pending .dot { background:#f0a900; } .pchip.approved .dot { background:#10a344; } .pchip.rejected .dot { background:#d03b3b; }
    .pchip b { color:var(--ink); font-variant-numeric:tabular-nums; }
    .pchip.on { background:var(--ink); border-color:var(--ink); color:var(--surface); } .pchip.on b { color:var(--surface); }
    .psearch { position:relative; display:flex; align-items:center; flex:0 1 300px; min-width:0; }
    .psearch svg { position:absolute; left:10px; width:15px; height:15px; color:var(--muted); pointer-events:none; }
    .psearch input { width:100%; padding:7px 30px 7px 32px; border:1px solid var(--line); border-radius:9px; background:var(--surface); color:var(--ink); font-size:12.5px; }
    .psearch input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px #2563eb22; }
    .psearch .clear { position:absolute; right:6px; display:grid; place-items:center; width:22px; height:22px; border-radius:6px; color:var(--muted); text-decoration:none; font-size:15px; }

    /* Table */
    .pay-table td { vertical-align:middle; }
    .pay-table tr.is-pending td:first-child { box-shadow:inset 3px 0 0 #f0a900; }
    .p-user { display:flex; align-items:center; gap:10px; min-width:0; }
    .p-av { display:grid; place-items:center; width:36px; height:36px; flex:0 0 36px; border-radius:11px; color:#fff; font-weight:800; font-size:13px; object-fit:cover; }
    .p-user strong { display:block; font-size:12.5px; } .p-user small { display:block; color:var(--muted); font-size:11px; }
    .trx { display:inline-flex; align-items:center; gap:6px; max-width:220px; padding:3px 8px; border:1px solid var(--line); border-radius:7px; background:var(--surface-2); color:var(--ink); font:700 11px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace; cursor:pointer; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; transition:.15s; }
    .trx svg { width:12px; height:12px; flex:0 0 12px; color:var(--muted); }
    .trx:hover { border-color:var(--primary); color:var(--primary); }
    .trx.copied { border-color:#10a344; color:#08752a; } .trx.copied svg { color:#10a344; }
    .flow { display:flex; align-items:center; gap:5px; margin-top:4px; color:var(--muted); font-size:11px; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .flow svg { width:12px; height:12px; }
    .p-pkg strong { display:block; font-size:12.5px; } .p-pkg small { display:block; color:var(--muted); font-size:11px; }
    .p-amount { font-size:14px; font-weight:800; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .p-time { font-size:12px; white-space:nowrap; } .p-time small { display:block; color:var(--muted); font-size:11px; }
    .spill { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
    .spill i { width:6px; height:6px; border-radius:50%; }
    .spill.pending { background:#fff4dc; color:#946200; } .spill.pending i { background:#f0a900; }
    .spill.approved { background:#e6f6ea; color:#08752a; } .spill.approved i { background:#10a344; }
    .spill.rejected { background:#fdecec; color:#c62828; } .spill.rejected i { background:#d03b3b; }
    [data-theme="dark"] .spill.pending { background:#3a2e10; color:#ffc94f; } [data-theme="dark"] .spill.approved { background:#123222; color:#69d998; } [data-theme="dark"] .spill.rejected { background:#3a1b1f; color:#ff9a9a; }
    .review-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border:0; border-radius:9px; background:var(--bkash); color:#fff; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; box-shadow:0 5px 12px color-mix(in srgb, var(--bkash) 30%, transparent); }
    .review-btn:hover { filter:brightness(1.08); } .review-btn svg { width:14px; height:14px; }
    .reviewed { display:grid; gap:1px; font-size:11px; color:var(--muted); max-width:220px; }
    .reviewed b { color:var(--ink); font-size:11.5px; }
    .reviewed .why { color:#c62828; white-space:normal; }
    .pay-empty { display:grid; place-items:center; gap:5px; padding:44px 16px; color:var(--muted); text-align:center; font-size:12.5px; }
    .pay-empty .ico { display:grid; place-items:center; width:50px; height:50px; border-radius:15px; background:var(--bkash-soft); color:var(--bkash); margin-bottom:4px; }
    .pay-empty .ico svg { width:24px; height:24px; } .pay-empty strong { color:var(--ink); font-size:14px; }
    .pay-pager { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:11px 16px; border-top:1px solid var(--line); font-size:12px; color:var(--muted); }
    .pay-pager nav { display:flex; gap:6px; }
    .pay-pager a, .pay-pager span.off { padding:5px 10px; border:1px solid var(--line); border-radius:8px; color:var(--ink); text-decoration:none; font-weight:600; }
    .pay-pager a:hover { border-color:var(--primary); color:var(--primary); } .pay-pager span.off { opacity:.45; }

    /* Review dialog */
    .rv { width:100%; max-width:min(520px, calc(100vw - 32px)); padding:0; border:0; background:transparent; color:var(--ink); }
    .rv::backdrop { background:rgba(7,17,39,.55); backdrop-filter:blur(3px); }
    .rv[open] .rv-box { animation:rv-pop .18s ease-out; }
    @keyframes rv-pop { from { opacity:0; transform:translateY(10px) scale(.98); } }
    .rv-box { position:relative; background:var(--surface); border:1px solid var(--line); border-radius:16px; box-shadow:0 30px 80px rgba(0,0,0,.3); overflow:hidden; max-height:calc(100vh - 32px); display:flex; flex-direction:column; }
    .rv-head { display:flex; align-items:center; gap:12px; padding:16px 50px 16px 18px; background:linear-gradient(135deg, var(--bkash), #a10f52); color:#fff; }
    .rv-head .p-av { border:2px solid rgba(255,255,255,.6); }
    .rv-head strong { display:block; font-size:14px; } .rv-head small { display:block; opacity:.85; font-size:11.5px; }
    .rv-amount { margin-left:auto; text-align:right; } .rv-amount b { display:block; font-size:20px; letter-spacing:-.4px; } .rv-amount small { opacity:.85; font-size:11px; }
    .rv-close { position:absolute; top:8px; right:8px; display:grid; place-items:center; width:28px; height:28px; border:0; border-radius:8px; background:rgba(255,255,255,.18); color:#fff; cursor:pointer; }
    .rv-close svg { width:15px; height:15px; }
    .rv-body { padding:14px 18px 18px; overflow-y:auto; display:grid; gap:14px; }
    .rv-dl { display:grid; margin:0; border:1px solid var(--line); border-radius:11px; overflow:hidden; }
    .rv-dl div { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:8px 12px; font-size:12.5px; }
    .rv-dl div + div { border-top:1px solid var(--line); }
    .rv-dl dt { color:var(--muted); } .rv-dl dd { margin:0; font-weight:700; text-align:right; font-variant-numeric:tabular-nums; }
    .rv-check { display:flex; gap:9px; padding:10px 12px; border-radius:11px; background:var(--bkash-soft); color:var(--ink); font-size:12px; line-height:1.5; }
    .rv-check svg { width:16px; height:16px; flex:0 0 16px; color:var(--bkash); margin-top:1px; }
    .rv-tabs { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
    .rv-tabs button { display:flex; align-items:center; justify-content:center; gap:7px; padding:9px; border:1px solid var(--line); border-radius:10px; background:var(--surface); color:var(--muted); font-weight:700; font-size:12.5px; cursor:pointer; }
    .rv-tabs button svg { width:15px; height:15px; }
    .rv-tabs button[aria-selected="true"][data-tab="approve"] { border-color:#10a344; background:#e6f6ea; color:#08752a; }
    .rv-tabs button[aria-selected="true"][data-tab="reject"] { border-color:#d03b3b; background:#fdecec; color:#c62828; }
    [data-theme="dark"] .rv-tabs button[aria-selected="true"][data-tab="approve"] { background:#123222; color:#69d998; }
    [data-theme="dark"] .rv-tabs button[aria-selected="true"][data-tab="reject"] { background:#3a1b1f; color:#ff9a9a; }
    .rv-pane { display:grid; gap:10px; }
    .rv-days { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .rv-days .input { width:90px; }
    .rv-days .preset { padding:5px 9px; border:1px solid var(--line); border-radius:8px; background:var(--surface); color:var(--ink); font-size:11.5px; font-weight:700; cursor:pointer; }
    .rv-days .preset:hover, .rv-days .preset.on { border-color:#10a344; color:#08752a; }
    .rv-until { font-size:12px; color:var(--muted); } .rv-until b { color:var(--ink); }
    .reasons { display:flex; flex-wrap:wrap; gap:6px; }
    .reasons button { padding:5px 9px; border:1px solid var(--line); border-radius:999px; background:var(--surface); color:var(--ink); font-size:11.5px; cursor:pointer; text-align:left; }
    .reasons button:hover { border-color:#d03b3b; color:#c62828; }
    .btn-approve { background:#10a344; border-color:#10a344; color:#fff; box-shadow:0 6px 14px rgba(16,163,68,.28); } .btn-approve:hover { color:#fff; filter:brightness(1.06); }
    .btn-reject { background:#d03b3b; border-color:#d03b3b; color:#fff; box-shadow:0 6px 14px rgba(208,59,59,.25); } .btn-reject:hover { color:#fff; filter:brightness(1.06); }
    .rv-actions { display:flex; justify-content:flex-end; gap:8px; }

    @media (max-width:1000px) { .pay-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:650px) {
        .pay-toolbar { padding:11px 12px; } .psearch { flex:1 1 100%; } .psearch input { font-size:16px; }
        .pay-stats { gap:8px; } .pay-stat { padding:11px; } .pay-stat strong { font-size:16px; }
        .table-wrap.stacked td[data-label="USER"]::before { content:none; }
        .table-wrap.stacked td[data-label="USER"] { justify-content:flex-start; }
        .table-wrap.stacked td[data-label=""] { justify-content:flex-end; }
        .table-wrap.stacked td[data-label=""]::before { content:none; }
        .pay-table tr.is-pending td:first-child { box-shadow:none; }
        .table-wrap.stacked tr.is-pending { border-color:#f0c35a; }
        .rv { max-width:100%; margin:auto 0 0; }
        .rv-box { border-radius:18px 18px 0 0; max-height:92vh; }
        .rv-actions .btn { flex:1; justify-content:center; padding:11px; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <p class="eyebrow">Billing</p>
            <h1><span class="bkash-mark" aria-hidden="true">b</span> bKash payments</h1>
            <p class="sub">Check each transaction in your bKash merchant app, then approve to activate the paid package.</p>
        </div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

@if(session('success'))<div class="pay-flash ok" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>{{ session('success') }}</div>@endif
@if(session('error'))<div class="pay-flash err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>{{ session('error') }}</div>@endif
@if($errors->any())<div class="pay-flash err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>{{ $errors->first() }}</div>@endif

<section class="pay-stats" aria-label="Payment summary">
    <a class="pay-stat pending" href="{{ $chipUrl('pending') }}">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg></span>
        <div><strong>{{ number_format((int) $summary->pending) }}</strong><span>Waiting for review</span>@if((float) $summary->pending_amount > 0)<small>{{ $taka($summary->pending_amount) }} to verify</small>@endif</div>
    </a>
    <a class="pay-stat approved" href="{{ $chipUrl('approved') }}">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
        <div><strong>{{ number_format((int) $summary->approved) }}</strong><span>Approved</span></div>
    </a>
    <a class="pay-stat rejected" href="{{ $chipUrl('rejected') }}">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg></span>
        <div><strong>{{ number_format((int) $summary->rejected) }}</strong><span>Rejected</span></div>
    </a>
    <div class="pay-stat revenue">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg></span>
        <div><strong>{{ $taka($summary->approved_amount ?? 0) }}</strong><span>Approved revenue</span></div>
    </div>
</section>

<section class="table-card">
    <div class="pay-toolbar">
        <nav class="pchips" aria-label="Filter by status">
            @foreach($filters as $key => $f)
                @php $on = ($status ?? null) === ($key ?: null); @endphp
                <a class="pchip {{ $f['tone'] }} {{ $on ? 'on' : '' }}" href="{{ $chipUrl($key ?: null) }}" @if($on) aria-current="true" @endif><i class="dot"></i>{{ $f['label'] }} <b>{{ number_format($f['count']) }}</b></a>
            @endforeach
        </nav>
        <form method="get" class="psearch" role="search">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search TrxID, bKash number, user or #ID" aria-label="Search payments" maxlength="100">
            @if($search)<a class="clear" href="{{ route('subscription-payments.index', array_filter(['status' => $status])) }}" aria-label="Clear search">×</a>@endif
        </form>
    </div>

    @if($payments->isEmpty())
        <div class="pay-empty">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg></span>
            @if($search || $status)
                <strong>No payments match this filter</strong><span>Try another search or <a href="{{ route('subscription-payments.index') }}" style="color:var(--primary);font-weight:700">show all payments</a>.</span>
            @else
                <strong>No payment requests yet</strong><span>When a user pays with bKash in the app and submits the TrxID, it appears here for review.</span>
            @endif
        </div>
    @else
        <div class="table-wrap"><table class="pay-table">
            <thead><tr><th>USER</th><th>TRANSACTION</th><th>PACKAGE</th><th>AMOUNT</th><th>SUBMITTED</th><th>STATUS</th><th></th></tr></thead>
            <tbody>
            @foreach($payments as $p)
                @php
                    $u = $p->user;
                    $review = [
                        'approve' => route('subscription-payments.approve', $p),
                        'reject' => route('subscription-payments.reject', $p),
                        'name' => $u?->name ?? 'Deleted user',
                        'email' => $u?->email ?? '',
                        'userId' => $u?->id,
                        'photo' => $u?->photo_url,
                        'initial' => $u?->initials ?? '?',
                        'trx' => $p->transaction_id,
                        'from' => $p->customer_bkash_number,
                        'to' => $p->merchant_bkash_number,
                        'amount' => $taka($p->amount),
                        'package' => $p->package_name.($p->package_id ? ' ('.$p->package_id.')' : ''),
                        'days' => (int) $p->duration_days,
                        'submitted' => $p->created_at?->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
                        'account' => $u?->account_type ? ucfirst($u->account_type) : '—',
                    ];
                @endphp
                <tr class="{{ $p->status === 'pending' ? 'is-pending' : '' }}">
                    <td>
                        <div class="p-user">
                            @if($u?->photo_url)<img class="p-av" src="{{ $u->photo_url }}" alt="" loading="lazy">@else<span class="p-av" style="background:{{ $tone($u?->name) }}">{{ $u?->initials ?? '?' }}</span>@endif
                            <div style="min-width:0"><strong>{{ $u?->name ?? 'Deleted user' }}@if($u) <span style="color:var(--muted);font-weight:600">#{{ $u->id }}</span>@endif</strong><small>{{ $u?->email ?? '—' }}</small></div>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="trx" data-copy="{{ $p->transaction_id }}" title="Copy TrxID">{{ $p->transaction_id }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg></button>
                        <div class="flow">{{ $p->customer_bkash_number }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 12h14m-5-5 5 5-5 5"/></svg> {{ $p->merchant_bkash_number }}</div>
                    </td>
                    <td class="p-pkg"><strong>{{ $p->package_name }}</strong><small>{{ $p->duration_days }} days{{ $p->package_id ? ' · '.$p->package_id : '' }}</small></td>
                    <td><span class="p-amount">{{ $taka($p->amount) }}</span></td>
                    <td class="p-time"><span title="{{ $p->created_at?->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}">{{ $p->created_at?->diffForHumans() }}</span><small>{{ $p->created_at?->timezone('Asia/Dhaka')->format('d M, h:i A') }}</small></td>
                    <td><span class="spill {{ $p->status }}"><i></i>{{ ucfirst($p->status) }}</span></td>
                    <td style="text-align:right">
                        @if($p->status === 'pending')
                            <button type="button" class="review-btn" data-review="{{ json_encode($review) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>Review</button>
                        @else
                            <div class="reviewed">
                                <b>{{ $p->status === 'approved' ? 'Approved' : 'Rejected' }} by {{ $p->reviewer?->username ?? 'admin' }}</b>
                                <span title="{{ $p->reviewed_at?->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}">{{ $p->reviewed_at?->diffForHumans() }}{{ $p->status === 'approved' ? ' · '.$p->duration_days.' days' : '' }}</span>
                                @if($p->rejection_reason)<span class="why">“{{ $p->rejection_reason }}”</span>@endif
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="pay-pager">
            <span>Showing {{ number_format($payments->firstItem()) }}–{{ number_format($payments->lastItem()) }} of {{ number_format($payments->total()) }}</span>
            @if($payments->hasPages())
                <nav aria-label="Pagination">@if($payments->onFirstPage())<span class="off">‹ Prev</span>@else<a href="{{ $payments->previousPageUrl() }}" rel="prev">‹ Prev</a>@endif @if($payments->hasMorePages())<a href="{{ $payments->nextPageUrl() }}" rel="next">Next ›</a>@else<span class="off">Next ›</span>@endif</nav>
            @endif
        </div>
    @endif
</section>

{{-- Review dialog --}}
<dialog class="rv" id="review-dialog" aria-labelledby="rv-name">
    <div class="rv-box">
        <div class="rv-head">
            <span class="p-av" data-rv="avatar" style="background:rgba(255,255,255,.2)"></span>
            <div style="min-width:0"><strong id="rv-name" data-rv="name"></strong><small data-rv="email"></small></div>
            <div class="rv-amount"><b data-rv="amount"></b><small>via bKash</small></div>
            <button type="button" class="rv-close" data-rv-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="rv-body">
            <dl class="rv-dl">
                <div><dt>Transaction ID</dt><dd><button type="button" class="trx" data-rv="trx" data-copy="" title="Copy TrxID"></button></dd></div>
                <div><dt>Paid from</dt><dd data-rv="from"></dd></div>
                <div><dt>Paid to (merchant)</dt><dd data-rv="to"></dd></div>
                <div><dt>Package</dt><dd data-rv="package"></dd></div>
                <div><dt>Submitted</dt><dd data-rv="submitted"></dd></div>
                <div><dt>Current account</dt><dd data-rv="account"></dd></div>
            </dl>
            <div class="rv-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg><span>Before approving, open your <b>bKash merchant app</b> and confirm this TrxID exists with the <b>same amount</b> from <b>this number</b>.</span></div>

            <div class="rv-tabs" role="tablist">
                <button type="button" role="tab" data-tab="approve" aria-selected="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>Approve</button>
                <button type="button" role="tab" data-tab="reject" aria-selected="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>Reject</button>
            </div>

            <form method="post" class="rv-pane" data-pane="approve" data-submit-once>
                @csrf
                <label class="field-label" for="rv-days">Paid access duration</label>
                <div class="rv-days">
                    <input class="input" type="number" id="rv-days" name="duration_days" min="1" max="3650" required>
                    <span class="hint">days</span>
                    @foreach([30, 90, 180, 365] as $d)<button type="button" class="preset" data-days="{{ $d }}">{{ $d === 365 ? '1 year' : $d.' days' }}</button>@endforeach
                </div>
                <span class="rv-until">Active from <b data-rv="start"></b> until <b data-rv="until"></b></span>
                <div class="rv-actions">
                    <button type="button" class="btn" data-rv-close>Cancel</button>
                    <button type="submit" class="btn btn-approve" data-loading-text="Approving…"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg><span data-label>Approve &amp; activate</span></button>
                </div>
            </form>

            <form method="post" class="rv-pane" data-pane="reject" data-submit-once hidden>
                @csrf
                <label class="field-label" for="rv-reason">Reason <span class="req">*</span> <span class="opt">the user will see this</span></label>
                <div class="reasons">@foreach($rejectReasons as $r)<button type="button" data-reason="{{ $r }}">{{ $r }}</button>@endforeach</div>
                <textarea class="input" id="rv-reason" name="rejection_reason" rows="3" maxlength="1000" required placeholder="Explain why this payment was rejected…"></textarea>
                <div class="rv-actions">
                    <button type="button" class="btn" data-rv-close>Cancel</button>
                    <button type="submit" class="btn btn-reject" data-loading-text="Rejecting…"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg><span data-label>Reject payment</span></button>
                </div>
            </form>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    // Copy TrxID (works for table chips and the dialog)
    const copy = async (btn) => {
        try { await navigator.clipboard.writeText(btn.dataset.copy); } catch (e) { return; }
        btn.classList.add('copied');
        const old = btn.title; btn.title = 'Copied!';
        setTimeout(() => { btn.classList.remove('copied'); btn.title = old; }, 1400);
    };
    document.addEventListener('click', (e) => { const b = e.target.closest('.trx[data-copy]'); if (b && b.dataset.copy) copy(b); });

    // Review dialog
    const dlg = document.getElementById('review-dialog');
    const rv = (k) => dlg.querySelector(`[data-rv="${k}"]`);
    const approveForm = dlg.querySelector('[data-pane="approve"]');
    const rejectForm = dlg.querySelector('[data-pane="reject"]');
    const days = dlg.querySelector('#rv-days');
    const reason = dlg.querySelector('#rv-reason');
    const fmt = (d) => d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const updateUntil = () => {
        const n = Math.max(1, parseInt(days.value || '0', 10) || 1);
        const start = new Date(); const end = new Date(); end.setDate(start.getDate() + n - 1);
        rv('start').textContent = fmt(start); rv('until').textContent = fmt(end);
        dlg.querySelectorAll('.preset').forEach((p) => p.classList.toggle('on', Number(p.dataset.days) === n));
    };
    const tab = (name) => {
        dlg.querySelectorAll('[data-tab]').forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === name)));
        approveForm.hidden = name !== 'approve'; rejectForm.hidden = name !== 'reject';
        (name === 'approve' ? days : reason).focus();
    };
    document.querySelectorAll('[data-review]').forEach((btn) => btn.addEventListener('click', () => {
        const d = JSON.parse(btn.dataset.review);
        approveForm.action = d.approve; rejectForm.action = d.reject;
        rv('name').textContent = d.name + (d.userId ? `  #${d.userId}` : '');
        rv('email').textContent = d.email;
        rv('amount').textContent = d.amount;
        rv('trx').textContent = d.trx; rv('trx').dataset.copy = d.trx;
        rv('from').textContent = d.from; rv('to').textContent = d.to;
        rv('package').textContent = d.package; rv('submitted').textContent = d.submitted; rv('account').textContent = d.account;
        const av = rv('avatar'); av.textContent = '';
        if (d.photo) { const img = document.createElement('img'); img.src = d.photo; img.alt = ''; img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:inherit'; av.append(img); } else { av.textContent = d.initial; }
        days.value = d.days || 30; reason.value = '';
        [approveForm, rejectForm].forEach((f) => { delete f.dataset.submitting; f.querySelectorAll('[type=submit]').forEach((b) => { b.disabled = false; }); });
        updateUntil();
        dlg.showModal();
        tab('approve');
    }));
    dlg.querySelectorAll('[data-tab]').forEach((t) => t.addEventListener('click', () => tab(t.dataset.tab)));
    dlg.querySelectorAll('[data-rv-close]').forEach((b) => b.addEventListener('click', () => dlg.close()));
    dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
    days.addEventListener('input', updateUntil);
    dlg.querySelectorAll('.preset').forEach((p) => p.addEventListener('click', () => { days.value = p.dataset.days; updateUntil(); }));
    dlg.querySelectorAll('[data-reason]').forEach((b) => b.addEventListener('click', () => { reason.value = b.dataset.reason; reason.focus(); }));
})();
</script>
@endpush
