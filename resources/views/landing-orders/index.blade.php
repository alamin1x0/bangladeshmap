@extends('layouts.app')

@php
    $statuses = \App\Models\LandingOrder::STATUSES;
    $tone = ['new' => '#2a78d6', 'contacted' => '#d08700', 'paid' => '#0f9d58', 'delivered' => '#6a4fd8', 'cancelled' => '#d93a3a'];
    $chipUrl = fn ($s) => route('landing-orders.index', array_filter(['status' => $s, 'q' => $search ?: null]));
    $taka = fn ($v) => '৳'.number_format((int) $v);
    $price = (int) ($settings->landing_price ?: \App\Http\Controllers\LandingController::DEFAULT_PRICE);
    $open = (bool) ($settings->landing_orders_open ?? true);
    $paidCount = (int) ($counts['paid'] ?? 0) + (int) ($counts['delivered'] ?? 0);
    $initial = fn ($n) => mb_strtoupper(mb_substr(trim((string) $n) ?: '?', 0, 1));
@endphp

@push('head')
<style>
    .lo-flash { display:flex; align-items:center; gap:8px; margin-bottom:12px; padding:9px 13px; border-radius:11px; font-size:12.5px; font-weight:600; background:#e9f8ef; color:#146c3b; }
    .lo-flash.err { background:#fdecec; color:#b42318; }
    [data-theme="dark"] .lo-flash { background:#123222; color:#69d998; } [data-theme="dark"] .lo-flash.err { background:#3a1b1f; color:#ff9a9a; }

    /* Summary: one soft strip, no borders */
    .lo-sum { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .lo-kpi { display:flex; align-items:center; gap:11px; padding:12px 14px; border-radius:14px; background:var(--surface); box-shadow:var(--shadow); text-decoration:none; color:inherit; min-width:0; transition:transform .15s; }
    a.lo-kpi:hover { transform:translateY(-1px); }
    .lo-kpi .ic { display:grid; place-items:center; width:36px; height:36px; flex:0 0 36px; border-radius:11px; background:color-mix(in srgb, var(--c) 13%, transparent); color:var(--c); }
    .lo-kpi .ic svg { width:18px; height:18px; }
    .lo-kpi strong { display:block; font-size:19px; letter-spacing:-.4px; line-height:1.2; font-variant-numeric:tabular-nums; }
    .lo-kpi span { display:block; color:var(--muted); font-size:11.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lo-kpi.hot { background:linear-gradient(135deg, color-mix(in srgb, var(--c) 10%, var(--surface)), var(--surface)); }

    /* Board */
    .lo-board { background:var(--surface); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }
    .lo-bar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 14px; flex-wrap:wrap; }
    .lo-chips { display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; min-width:0; padding:1px; }
    .lo-chips::-webkit-scrollbar { display:none; }
    .lo-chip { --c:var(--ink); display:inline-flex; align-items:center; gap:6px; flex:0 0 auto; padding:6px 11px; border-radius:999px; background:color-mix(in srgb, var(--c) 8%, transparent); color:color-mix(in srgb, var(--c) 85%, var(--ink)); font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap; transition:background .15s; }
    .lo-chip:hover { background:color-mix(in srgb, var(--c) 15%, transparent); }
    .lo-chip b { font-variant-numeric:tabular-nums; padding:0 6px; border-radius:99px; background:color-mix(in srgb, var(--c) 14%, transparent); font-size:11px; line-height:17px; }
    .lo-chip.on { background:var(--c); color:#fff; }
    .lo-chip.on b { background:#ffffff33; }
    .lo-chip.all { --c:#475569; }
    [data-theme="dark"] .lo-chip.all { --c:#94a3b8; }
    .lo-search { position:relative; display:flex; align-items:center; flex:0 1 280px; min-width:0; }
    .lo-search svg { position:absolute; left:11px; width:15px; height:15px; color:var(--muted); pointer-events:none; }
    .lo-search input { width:100%; padding:8px 30px 8px 33px; border:0; border-radius:10px; background:var(--bg, #f1f4f9); color:var(--ink); font-size:12.5px; }
    [data-theme="dark"] .lo-search input { background:#ffffff0d; }
    .lo-search input:focus { outline:none; box-shadow:0 0 0 3px #2563eb33; }
    .lo-search .clear { position:absolute; right:6px; width:22px; height:22px; display:grid; place-items:center; color:var(--muted); text-decoration:none; font-size:15px; }

    /* Rows: no lines, just spacing + hover */
    .lo-list { padding:0 8px 8px; }
    .lo-head, .lo-row { display:grid; grid-template-columns:minmax(190px,1.3fr) minmax(150px,1.2fr) 120px 150px 70px; gap:14px; align-items:center; padding:9px 10px; }
    .lo-head { font-size:10.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--muted); padding-top:2px; padding-bottom:6px; }
    .lo-row { border-radius:12px; transition:background .12s; }
    .lo-row:hover { background:color-mix(in srgb, var(--primary) 5%, transparent); }
    .lo-row.is-new { background:color-mix(in srgb, #2a78d6 5%, transparent); }
    .lo-who { display:flex; align-items:center; gap:10px; min-width:0; }
    .lo-av { display:grid; place-items:center; width:36px; height:36px; flex:0 0 36px; border-radius:50%; background:color-mix(in srgb, var(--c) 14%, transparent); color:var(--c); font-weight:800; font-size:14px; }
    .lo-who > div { min-width:0; }
    .lo-who strong { display:block; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lo-who strong small { font-weight:600; color:var(--muted); font-size:11px; margin-left:4px; font-family:ui-monospace,monospace; }
    .lo-phone { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:var(--primary); text-decoration:none; font-variant-numeric:tabular-nums; }
    .lo-copy { border:0; background:none; color:var(--muted); cursor:pointer; padding:0 3px; font-size:11px; line-height:1; }
    .lo-copy:hover { color:var(--primary); }
    .lo-info { min-width:0; font-size:12.5px; }
    .lo-info .d { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lo-info .m { display:block; color:var(--muted); font-size:11.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lo-info .n { display:block; color:#b77900; font-size:11.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    [data-theme="dark"] .lo-info .n { color:#ffc94f; }
    .lo-when { font-size:12px; white-space:nowrap; } .lo-when small { display:block; color:var(--muted); font-size:11px; }
    .lo-pill { position:relative; display:inline-flex; align-items:center; }
    .lo-pill::before { content:""; position:absolute; left:10px; width:7px; height:7px; border-radius:50%; background:var(--c); pointer-events:none; }
    .lo-pill select { appearance:none; -webkit-appearance:none; padding:6px 26px 6px 23px; border:0; border-radius:999px; background:color-mix(in srgb, var(--c) 13%, transparent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238592a8' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 8px center / 12px; color:color-mix(in srgb, var(--c) 80%, var(--ink)); font-size:12px; font-weight:700; cursor:pointer; }
    .lo-pill select:focus { outline:none; box-shadow:0 0 0 3px color-mix(in srgb, var(--c) 30%, transparent); }
    .lo-pill select option { color:var(--ink); background:var(--surface); }
    .lo-amt { display:block; font-size:11px; color:var(--muted); margin:3px 0 0 4px; }
    .lo-act { display:flex; gap:2px; justify-content:flex-end; }
    .lo-ib { display:grid; place-items:center; width:30px; height:30px; border:0; border-radius:9px; background:transparent; color:var(--muted); cursor:pointer; }
    .lo-ib:hover { background:color-mix(in srgb, var(--primary) 10%, transparent); color:var(--primary); }
    .lo-ib.danger:hover { background:#fdecec; color:#c62828; }
    [data-theme="dark"] .lo-ib.danger:hover { background:#3a1b1f; }
    .lo-ib svg { width:15px; height:15px; }

    .lo-empty { padding:50px 16px 56px; text-align:center; color:var(--muted); font-size:13px; }
    .lo-empty .ic { width:52px; height:52px; margin:0 auto 12px; border-radius:16px; display:grid; place-items:center; background:var(--primary-soft); color:var(--primary); }
    .lo-empty .ic svg { width:24px; height:24px; }
    .lo-empty strong { display:block; color:var(--ink); font-size:14px; margin-bottom:3px; }
    .lo-pager { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 18px 14px; font-size:12px; color:var(--muted); }
    .lo-pager nav { display:flex; gap:6px; }
    .lo-pager a, .lo-pager span.off { padding:5px 11px; border-radius:8px; background:color-mix(in srgb, var(--primary) 8%, transparent); color:var(--primary); text-decoration:none; font-weight:700; }
    .lo-pager span.off { opacity:.4; color:var(--muted); background:transparent; }

    /* Live badge + settings button */
    .lo-live { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:99px; background:#e9f8ef; color:#0f7a43; }
    .lo-live i { width:7px; height:7px; border-radius:50%; background:currentColor; box-shadow:0 0 0 3px #0f9d5826; }
    .lo-live.off { background:#fdecec; color:#c62828; }
    [data-theme="dark"] .lo-live { background:#123222; color:#69d998; } [data-theme="dark"] .lo-live.off { background:#3a1b1f; color:#ff9a9a; }

    /* Dialogs */
    dialog.lo-dlg { border:0; border-radius:16px; padding:0; width:min(420px, calc(100% - 24px)); background:var(--surface); color:var(--ink); box-shadow:0 30px 70px -20px #0008; }
    dialog.lo-dlg::backdrop { background:#0f172a80; backdrop-filter:blur(2px); }
    .lo-dlg form { padding:18px; display:grid; gap:12px; }
    .lo-dlg h3 { font-size:15px; margin:0; display:flex; justify-content:space-between; align-items:center; }
    .lo-dlg h3 button { border:0; background:none; color:var(--muted); font-size:20px; cursor:pointer; line-height:1; }
    .lo-dlg p.hint { margin:-6px 0 0; font-size:12px; color:var(--muted); }
    .lo-dlg label.f { display:block; font-size:11.5px; font-weight:700; color:var(--muted); margin-bottom:4px; }
    .lo-dlg .grid2 { display:grid; grid-template-columns:110px 1fr; gap:10px; }
    .lo-dlg textarea { min-height:90px; resize:vertical; }
    .lo-dlg .foot { display:flex; justify-content:flex-end; gap:8px; }
    .lo-toggle { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 12px; border-radius:12px; background:color-mix(in srgb, var(--primary) 5%, transparent); cursor:pointer; }
    .lo-toggle b { display:block; font-size:12.5px; } .lo-toggle small { color:var(--muted); font-size:11.5px; }
    .lo-toggle input { position:absolute; opacity:0; pointer-events:none; }
    .lo-toggle .track { position:relative; width:36px; height:20px; flex:0 0 36px; border-radius:99px; background:#cbd5e1; transition:background .15s; }
    .lo-toggle .track::after { content:""; position:absolute; left:2px; top:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px #0003; transition:left .15s; }
    .lo-toggle input:checked + .track { background:#0f9d58; }
    .lo-toggle input:checked + .track::after { left:18px; }
    .lo-toggle input:focus-visible + .track { box-shadow:0 0 0 3px #2563eb44; }
    .lo-quick { display:flex; gap:6px; flex-wrap:wrap; }
    .lo-quick button { border:0; border-radius:99px; padding:4px 10px; font-size:11.5px; font-weight:600; background:color-mix(in srgb, var(--primary) 8%, transparent); color:var(--primary); cursor:pointer; }

    @media (max-width:1100px) {
        .lo-head { display:none; }
        .lo-row { grid-template-columns:minmax(0,1fr) auto; gap:8px 12px; padding:12px; }
        .lo-info, .lo-when { grid-column:1; }
        .lo-row > .lo-stat { grid-column:2; grid-row:1; justify-self:end; }
        .lo-act { grid-column:2; grid-row:2 / span 2; align-self:end; }
        .lo-info { padding-left:46px; }
        .lo-when { padding-left:46px; display:flex; gap:6px; } .lo-when small { display:inline; }
        .lo-amt { text-align:right; }
    }
    @media (max-width:650px) {
        .lo-sum { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .lo-kpi { padding:10px 11px; }
        .lo-search { flex:1 1 100%; }
        .lo-list { padding:0 4px 6px; }
        .lo-row { margin-bottom:4px; background:color-mix(in srgb, var(--primary) 3%, transparent); }
        .lo-when small { display:none; }
        .top-actions .lo-hide-sm { display:none; }
        .lo-pill select { padding:3px 24px 3px 21px; font-size:16px; }
        .lo-pill::before { left:9px; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <p class="eyebrow">Website</p>
            <h1>Book orders <span class="lo-live {{ $open ? '' : 'off' }}"><i></i>{{ $open ? 'Taking orders' : 'Orders closed' }} · {{ $taka($price) }}</span></h1>
            <p class="sub">Call new customers, confirm the bKash payment, then mark the order delivered.</p>
        </div>
    </div>
    <div class="top-actions">
        <a class="btn lo-hide-sm" href="{{ route('home') }}" target="_blank" rel="noopener">View site ↗</a>
        <button class="btn" type="button" id="open-settings"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>Settings</button>
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
    </div>
</header>

@if(session('success'))<div class="lo-flash" role="status">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="lo-flash err" role="alert">{{ $errors->first() }}</div>@endif

<section class="lo-sum" aria-label="Order summary">
    <a class="lo-kpi hot" style="--c:#2a78d6" href="{{ $chipUrl('new') }}">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg></span>
        <div><strong>{{ number_format((int) ($counts['new'] ?? 0)) }}</strong><span>New · call these</span></div>
    </a>
    <div class="lo-kpi" style="--c:#d08700">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg></span>
        <div><strong>{{ number_format($today) }}</strong><span>Orders today</span></div>
    </div>
    <a class="lo-kpi" style="--c:#0f9d58" href="{{ $chipUrl('paid') }}">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
        <div><strong>{{ number_format($paidCount) }}</strong><span>Paid & delivered</span></div>
    </a>
    <div class="lo-kpi" style="--c:#6a4fd8">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg></span>
        <div><strong>{{ $taka($revenue) }}</strong><span>Revenue</span></div>
    </div>
</section>

<section class="lo-board">
    <div class="lo-bar">
        <nav class="lo-chips" aria-label="Filter by status">
            <a class="lo-chip all {{ ! $status ? 'on' : '' }}" href="{{ $chipUrl(null) }}" @if(! $status) aria-current="true" @endif>All <b>{{ number_format($total) }}</b></a>
            @foreach($statuses as $key => $label)
                <a class="lo-chip {{ $status === $key ? 'on' : '' }}" style="--c:{{ $tone[$key] }}" href="{{ $chipUrl($key) }}" @if($status === $key) aria-current="true" @endif>{{ $label }} <b>{{ number_format((int) ($counts[$key] ?? 0)) }}</b></a>
            @endforeach
        </nav>
        <form method="get" class="lo-search" role="search">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name, phone, district, #ID" aria-label="Search orders" maxlength="100">
            @if($search)<a class="clear" href="{{ route('landing-orders.index', array_filter(['status' => $status])) }}" aria-label="Clear search">×</a>@endif
        </form>
    </div>

    @if($orders->isEmpty())
        <div class="lo-empty">
            <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5z"/><path d="M20 5.5A1.5 1.5 0 0 0 18.5 4H13v16h5.5a1.5 1.5 0 0 0 1.5-1.5z"/></svg></div>
            <strong>{{ $search || $status ? 'No orders match this filter' : 'No orders yet' }}</strong>
            {{ $search || $status ? 'Try another status or clear the search.' : 'Orders from the landing page form will show up here.' }}
        </div>
    @else
        <div class="lo-list" role="list">
            <div class="lo-head" aria-hidden="true"><span>Customer</span><span>District · message</span><span>Received</span><span>Status</span><span></span></div>
            @foreach($orders as $o)
                @php $c = $tone[$o->status] ?? '#64748b'; @endphp
                <div class="lo-row {{ $o->status === 'new' ? 'is-new' : '' }}" role="listitem" style="--c:{{ $c }}">
                    <div class="lo-who">
                        <span class="lo-av" aria-hidden="true">{{ $initial($o->name) }}</span>
                        <div>
                            <strong>{{ $o->name }} <small>#{{ $o->id }}</small></strong>
                            <a class="lo-phone" href="tel:{{ $o->phone }}">{{ $o->phone }}</a><button type="button" class="lo-copy" data-copy="{{ $o->phone }}" aria-label="Copy phone number">⧉</button>
                        </div>
                    </div>
                    <div class="lo-info">
                        <span class="d">{{ $o->district ?: 'No district' }}</span>
                        @if($o->address)<span class="m" title="{{ $o->address }}">“{{ $o->address }}”</span>@endif
                        @if($o->admin_note)<span class="n" title="{{ $o->admin_note }}">📝 {{ $o->admin_note }}</span>@endif
                    </div>
                    @php $local = $o->created_at->copy()->timezone('Asia/Dhaka'); @endphp
                    <div class="lo-when" title="{{ $local->format('d M Y, h:i A') }} (Bangladesh time)">{{ $o->created_at->diffForHumans() }}<small>{{ $local->format('d M, h:i A') }}</small></div>
                    <div class="lo-stat">
                        <form method="post" action="{{ route('landing-orders.update', $o) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="admin_note" value="{{ $o->admin_note }}">
                            <label class="lo-pill">
                                <select name="status" aria-label="Status of order #{{ $o->id }}" data-autosubmit>
                                    @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($o->status === $key)>{{ $label }}</option>@endforeach
                                </select>
                            </label>
                        </form>
                        <span class="lo-amt">{{ $taka($o->amount) }}</span>
                    </div>
                    <div class="lo-act">
                        <button type="button" class="lo-ib" data-note="{{ $o->id }}" data-note-name="{{ $o->name }}" data-note-text="{{ $o->admin_note }}" data-note-status="{{ $o->status }}" data-note-action="{{ route('landing-orders.update', $o) }}" aria-label="Add note to order #{{ $o->id }}" title="Note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>
                        <form method="post" action="{{ route('landing-orders.destroy', $o) }}" data-confirm="This order will be permanently removed." data-confirm-title="Delete order #{{ $o->id }}?" data-confirm-item="{{ $o->name }} · {{ $o->phone }}" data-confirm-button="Delete order">
                            @csrf @method('DELETE')
                            <button class="lo-ib danger" type="submit" aria-label="Delete order #{{ $o->id }}" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="lo-pager">
            <span>Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ number_format($orders->total()) }}</span>
            @if($orders->hasPages())
                <nav aria-label="Pagination">
                    @if($orders->onFirstPage())<span class="off">‹ Prev</span>@else<a href="{{ $orders->previousPageUrl() }}">‹ Prev</a>@endif
                    @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">Next ›</a>@else<span class="off">Next ›</span>@endif
                </nav>
            @endif
        </div>
    @endif
</section>

<dialog class="lo-dlg" id="settings-dialog" aria-labelledby="settings-title">
    <form method="post" action="{{ route('landing-orders.settings') }}" data-submit-once data-loading-text="Saving…">
        @csrf @method('PUT')
        <h3 id="settings-title">Landing page settings <button type="button" data-close aria-label="Close">×</button></h3>
        <p class="hint">Changes show on the public page right away.</p>
        <div><label class="f" for="lp">Price (৳)</label><input class="input" id="lp" type="number" name="landing_price" min="1" max="1000000" value="{{ old('landing_price', $price) }}" required></div>
        <p class="hint" style="margin:0">📞 Contact phone, email & WhatsApp are now in <a href="{{ route('web-settings.edit', ['tab' => 'contact']) }}">Web Settings</a>.</p>
        <label class="lo-toggle">
            <span><b>Accept new orders</b><small>Turn off to close the order form temporarily.</small></span>
            <input type="checkbox" name="landing_orders_open" value="1" @checked($open)><span class="track"></span>
        </label>
        <div class="foot">
            <button type="button" class="btn" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary"><span data-label>Save settings</span></button>
        </div>
    </form>
</dialog>

<dialog class="lo-dlg" id="note-dialog" aria-labelledby="note-title">
    <form method="post" id="note-form" data-submit-once data-loading-text="Saving…">
        @csrf @method('PATCH')
        <h3 id="note-title">Note <button type="button" data-close aria-label="Close">×</button></h3>
        <p class="hint" id="note-sub"></p>
        <input type="hidden" name="status" id="note-status">
        <textarea class="input" name="admin_note" id="note-text" maxlength="1000" placeholder="Write a private note…"></textarea>
        <div class="lo-quick">
            @foreach(['Called, no answer', 'Will pay by bKash tonight', 'Payment received', 'Sent on WhatsApp'] as $q)<button type="button" data-quick="{{ $q }}">{{ $q }}</button>@endforeach
        </div>
        <div class="foot">
            <button type="button" class="btn" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary"><span data-label>Save note</span></button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    document.querySelectorAll('select[data-autosubmit]').forEach(s => s.addEventListener('change', () => s.form.submit()));
    document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = '✓'; setTimeout(() => b.textContent = '⧉', 1200); } catch (e) {}
    }));
    document.querySelectorAll('dialog.lo-dlg').forEach(d => {
        d.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => d.close()));
        d.addEventListener('click', e => { if (e.target === d) d.close(); });
    });
    const settings = document.getElementById('settings-dialog');
    document.getElementById('open-settings').addEventListener('click', () => settings.showModal());
    @if($errors->has('landing_price') || $errors->has('landing_phone')) settings.showModal(); @endif

    const note = document.getElementById('note-dialog'), form = document.getElementById('note-form'), text = document.getElementById('note-text');
    document.querySelectorAll('[data-note]').forEach(b => b.addEventListener('click', () => {
        form.action = b.dataset.noteAction;
        document.getElementById('note-title').firstChild.textContent = 'Note · order #' + b.dataset.note + ' ';
        document.getElementById('note-sub').textContent = b.dataset.noteName + ' — only admins can see this.';
        document.getElementById('note-status').value = b.dataset.noteStatus;
        text.value = b.dataset.noteText || '';
        note.showModal(); text.focus();
    }));
    document.querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => { text.value = b.dataset.quick; text.focus(); }));
})();
</script>
@endpush
