@extends('layouts.app')

@php
    $fmt = fn ($n) => number_format((int) $n);
    $pct = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100) : 0;
    $u = $appUsers;
    $loc = $locations;

    /** Delta chip state + text for a percent change (null = no baseline). */
    $delta = function (?float $p, int $current): array {
        if ($p === null) return ['up', $current > 0 ? 'New' : '—'];
        if ($p == 0) return ['flat', 'No change'];
        return [$p > 0 ? 'up' : 'down', ($p > 0 ? '+' : '').rtrim(rtrim(number_format($p, 1), '0'), '.').'%'];
    };
    [$newState, $newText] = $delta($u['growthPercent'], $u['newThisMonth']);
    [$activeState, $activeText] = $delta($u['activeGrowthPercent'], $u['activeThisMonth']);

    $monthlyValues = array_column($monthlySignups, 'value');
    $monthlyMax = max($monthlyValues ?: [0]) ?: 1;
    $monthlyTotal = array_sum($monthlyValues);

    $thisMonthShort = \Illuminate\Support\Str::before($u['thisMonthLabel'], ' ');
    $prevMonthShort = \Illuminate\Support\Str::before($u['prevMonthLabel'], ' ');
    $compare = [
        ['label' => 'New users', 'hint' => 'same days', 'now' => $u['newThisMonth'], 'prev' => $u['newPrevSamePeriod'], 'state' => $newState, 'text' => $newText],
        ['label' => 'Active users', 'hint' => 'opened the app', 'now' => $u['activeThisMonth'], 'prev' => $u['activePrevMonth'], 'state' => $activeState, 'text' => $activeText],
    ];
    $compareMax = max(1, ...array_map(fn ($r) => max($r['now'], $r['prev']), $compare));

    $platformMax = max(array_column($platforms, 'value') ?: [1]);
    $platformTotal = array_sum(array_column($platforms, 'value'));
    $divisionMax = max(array_column($loc['byDivision'], 'upazilas') ?: [1]);

    $statusRows = [
        ['key' => 'active', 'label' => 'Active', 'value' => $u['active']],
        ['key' => 'inactive', 'label' => 'Inactive', 'value' => $u['inactive']],
        ['key' => 'blocked', 'label' => 'Blocked', 'value' => $u['blocked']],
    ];

    $arrow = fn (string $state) => match ($state) {
        'up' => '<svg viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><path d="M6 2 11 9H1z"/></svg>',
        'down' => '<svg viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><path d="M6 10 1 3h10z"/></svg>',
        default => '',
    };
@endphp

@push('head')
<style>
    :root { --s1:#2a78d6; --prev:#c9d2df; --good:#0ca30c; --good-text:#00720a; --warn:#fab219; --crit:#d03b3b; --tip-bg:#101a31; --tip-ink:#f3f6fc; }
    [data-theme="dark"] { --s1:#3987e5; --prev:#3a4a6b; --good-text:#3ccf3c; --crit:#e05a5a; --tip-bg:#e9eef8; --tip-ink:#0d1425; }

    .dash { display:grid; gap:12px; }
    .link { color:var(--primary); font-weight:700; font-size:12px; text-decoration:none; white-space:nowrap; }
    .link:hover { text-decoration:underline; }
    .quick-add { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; border-radius:9px; background:var(--primary); color:#fff; font-weight:700; font-size:12px; text-decoration:none; white-space:nowrap; box-shadow:0 5px 12px #2563eb30; }
    .quick-add:hover { filter:brightness(1.08); } .quick-add svg { width:13px; height:13px; }

    /* KPI tiles */
    .kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .kpi { display:flex; align-items:center; gap:11px; padding:12px 14px; background:var(--surface); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow); min-width:0; color:inherit; text-decoration:none; transition:border-color .2s, transform .2s; }
    a.kpi:hover { border-color:var(--primary); transform:translateY(-1px); }
    .kpi-icon { display:grid; place-items:center; width:34px; height:34px; flex:0 0 34px; border-radius:10px; color:var(--primary); background:var(--primary-soft); }
    .kpi-icon svg { width:17px; height:17px; }
    .kpi-body { min-width:0; flex:1; display:grid; }
    .kpi-label { color:var(--muted); font-size:11.5px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .kpi-row { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
    .kpi-value { font-size:20px; font-weight:800; letter-spacing:-.5px; line-height:1.3; font-variant-numeric:tabular-nums; }
    .kpi-meta { color:var(--muted); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .kpi-meta b { color:var(--ink); }
    .kpi.hero { background:linear-gradient(135deg,#1f4fbf,#2a78d6); border-color:transparent; color:#fff; }
    .kpi.hero .kpi-label, .kpi.hero .kpi-meta { color:#d6e4ff; } .kpi.hero .kpi-meta b { color:#fff; }
    .kpi.hero .kpi-icon { background:#ffffff24; color:#fff; }
    .live-dot { display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--good); margin-right:5px; vertical-align:middle; animation:pulse 2s infinite; }
    @keyframes pulse { 0% { box-shadow:0 0 0 0 #0ca30c80; } 70%,100% { box-shadow:0 0 0 6px #0ca30c00; } }
    @media (prefers-reduced-motion: reduce) { .live-dot { animation:none; } }
    .chip { display:inline-flex; align-items:center; gap:3px; padding:1px 6px; border-radius:20px; font-size:10.5px; font-weight:700; white-space:nowrap; }
    .chip svg { width:8px; height:8px; }
    .chip.up { color:var(--good-text); background:#e6f6ea; } .chip.down { color:var(--crit); background:#fdecec; } .chip.flat { color:var(--muted); background:var(--surface-2); }
    [data-theme="dark"] .chip.up { background:#123222; } [data-theme="dark"] .chip.down { background:#3a1b1f; }

    /* Panels */
    .grid-2 { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(0,1fr); gap:12px; }
    .grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .panel { background:var(--surface); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow); padding:14px; min-width:0; display:grid; gap:11px; align-content:start; }
    .panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .panel-head h3 { margin:0; font-size:13px; }
    .panel-head p { margin:1px 0 0; color:var(--muted); font-size:11.5px; }
    .panel-figure { font-size:17px; font-weight:800; text-align:right; white-space:nowrap; line-height:1.15; font-variant-numeric:tabular-nums; }
    .panel-figure small { display:block; font-size:10px; color:var(--muted); font-weight:600; }
    .sub-head { margin:4px 0 -3px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--muted); }
    .empty { display:grid; place-items:center; text-align:center; gap:2px; padding:18px 12px; border-radius:10px; background:var(--surface-2); color:var(--muted); font-size:12px; }
    .empty strong { color:var(--ink); font-size:12.5px; }

    /* Column chart: values on top, no gridlines */
    .cols { display:flex; align-items:flex-end; gap:5px; height:118px; padding-top:14px; }
    .col { position:relative; flex:1 1 0; height:100%; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; outline:none; border-radius:6px; }
    .col:hover, .col:focus-visible { background:var(--surface-2); }
    .col-value { font-size:10px; font-weight:700; color:var(--muted); margin-bottom:3px; font-variant-numeric:tabular-nums; }
    .col.current .col-value { color:var(--ink); }
    .col-bar { width:min(100%,22px); min-height:2px; border-radius:4px 4px 1px 1px; background:var(--s1); opacity:.45; transition:opacity .15s; }
    .col.current .col-bar, .col:hover .col-bar, .col:focus-visible .col-bar { opacity:1; }
    .col-axis { display:flex; gap:5px; margin-top:5px; font-size:10px; color:var(--muted); }
    .col-axis span { flex:1 1 0; text-align:center; } .col-axis span.current { color:var(--ink); font-weight:700; }

    /* Month comparison */
    .legend { display:flex; gap:12px; font-size:11px; color:var(--muted); }
    .legend span { display:inline-flex; align-items:center; gap:5px; }
    .key { width:9px; height:9px; border-radius:3px; background:var(--s1); } .key.prev { background:var(--prev); }
    .compare { display:grid; gap:12px; }
    .compare-head { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:5px; }
    .compare-head strong { font-size:12px; } .compare-head small { color:var(--muted); font-size:10.5px; margin-left:4px; }
    .compare-row { display:grid; grid-template-columns:26px 1fr 30px; align-items:center; gap:7px; font-size:11px; color:var(--muted); outline:none; }
    .compare-row + .compare-row { margin-top:4px; }
    .compare-row b { color:var(--ink); text-align:right; font-variant-numeric:tabular-nums; }
    .track { height:8px; border-radius:4px; background:var(--surface-2); overflow:hidden; }
    .fill { height:100%; min-width:3px; border-radius:4px; background:var(--s1); } .fill.prev { background:var(--prev); }

    /* Status */
    .stackbar { display:flex; gap:2px; height:8px; border-radius:4px; overflow:hidden; background:var(--surface-2); }
    .stackbar div { min-width:3px; outline:none; }
    .st-active { background:var(--good); } .st-inactive { background:var(--warn); } .st-blocked { background:var(--crit); }
    .status-list { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:6px; margin:0; padding:0; list-style:none; }
    .status-list li { display:grid; gap:1px; padding:7px 9px; border-radius:9px; background:var(--surface-2); }
    .status-list .status-name { display:flex; align-items:center; gap:5px; font-size:11px; color:var(--muted); font-weight:600; }
    .status-icon { display:grid; place-items:center; width:14px; height:14px; border-radius:4px; color:#fff; }
    .status-icon svg { width:8px; height:8px; } .status-icon.st-inactive { color:#0b0b0b; }
    .status-list strong { font-size:15px; font-variant-numeric:tabular-nums; } .status-list strong small { font-size:10.5px; color:var(--muted); font-weight:600; margin-left:3px; }

    /* Horizontal bars */
    .hbars { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
    .hbar { display:grid; gap:3px; outline:none; border-radius:6px; }
    .hbar-top { display:flex; justify-content:space-between; gap:10px; font-size:12px; }
    .hbar-name { font-weight:600; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .hbar-name small { color:var(--muted); font-weight:400; margin-left:4px; }
    .hbar-val { color:var(--muted); white-space:nowrap; font-variant-numeric:tabular-nums; font-size:11.5px; } .hbar-val b { color:var(--ink); }
    .hbar .track { height:5px; border-radius:3px; } .hbar .fill { border-radius:3px; }
    .hbar:hover .fill, .hbar:focus-visible .fill { filter:brightness(1.12); }

    /* Lists */
    .feed { display:grid; gap:2px; margin:0; padding:0; list-style:none; }
    .feed li { display:grid; grid-template-columns:28px minmax(0,1fr) auto; align-items:center; gap:9px; padding:5px 6px; margin:0 -6px; border-radius:8px; }
    .feed li:hover { background:var(--surface-2); }
    .feed-avatar { display:grid; place-items:center; width:28px; height:28px; border-radius:8px; color:var(--primary); background:var(--primary-soft); font-weight:800; font-size:11.5px; }
    .feed-main { min-width:0; } .feed-main strong { display:block; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .feed-main span { display:block; color:var(--muted); font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .feed-side { text-align:right; color:var(--muted); font-size:10.5px; white-space:nowrap; }
    .pill { display:inline-block; padding:1px 7px; border-radius:20px; font-size:10px; font-weight:700; background:var(--surface-2); color:var(--muted); }
    .pill.active { background:#e6f6ea; color:var(--good-text); } .pill.blocked { background:#fdecec; color:var(--crit); } .pill.inactive { background:#fff4dc; color:#8a5a00; }
    [data-theme="dark"] .pill.active { background:#123222; } [data-theme="dark"] .pill.blocked { background:#3a1b1f; } [data-theme="dark"] .pill.inactive { background:#3a2e10; color:#fab219; }
    .mini-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:6px; }
    .mini-stats div { padding:7px 9px; border-radius:9px; background:var(--surface-2); }
    .mini-stats strong { display:block; font-size:15px; font-variant-numeric:tabular-nums; }
    .mini-stats span { color:var(--muted); font-size:10.5px; }

    /* Coverage panel */
    .coverage-body { display:grid; grid-template-columns:minmax(0,250px) minmax(0,1fr); gap:18px; align-items:start; }
    .cov-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .cov-stat { display:grid; gap:1px; padding:10px 11px; border-radius:10px; background:var(--surface-2); color:inherit; text-decoration:none; border:1px solid transparent; transition:border-color .15s; }
    .cov-stat:hover { border-color:var(--primary); }
    .cov-stat span { color:var(--muted); font-size:11px; font-weight:600; }
    .cov-stat strong { font-size:20px; letter-spacing:-.5px; font-variant-numeric:tabular-nums; line-height:1.2; }
    .cov-stat small { color:var(--muted); font-size:10.5px; }
    .cov-bars { grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px 20px; }

    details.data-table summary { cursor:pointer; color:var(--primary); font-size:11px; font-weight:700; width:max-content; }
    details.data-table table { margin-top:6px; } details.data-table th, details.data-table td { padding:5px 9px; }

    .viz-tip { position:fixed; z-index:200; pointer-events:none; max-width:230px; padding:6px 9px; border-radius:8px; background:var(--tip-bg); color:var(--tip-ink); font-size:11.5px; line-height:1.45; box-shadow:0 10px 25px #0003; opacity:0; transition:opacity .12s; white-space:pre-line; }
    .viz-tip.show { opacity:1; }

    @media (max-width:1240px) {
        .kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .grid-3 { grid-template-columns:repeat(2,minmax(0,1fr)); } .grid-3 > :last-child { grid-column:1 / -1; }
    }
    @media (max-width:1000px) {
        .grid-2 { grid-template-columns:minmax(0,1fr); }
        .coverage-body { grid-template-columns:minmax(0,1fr); }
        .cov-stats { grid-template-columns:repeat(4,minmax(0,1fr)); }
    }
    @media (max-width:760px) {
        .grid-3 { grid-template-columns:minmax(0,1fr); }
        .cov-bars { grid-template-columns:minmax(0,1fr); }
        .cov-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:650px) {
        .kpi-grid { gap:8px; }
        .kpi { flex-direction:column; align-items:flex-start; gap:6px; padding:11px; }
        .kpi-icon { width:28px; height:28px; flex-basis:28px; border-radius:8px; } .kpi-icon svg { width:15px; height:15px; }
        .kpi-value { font-size:18px; }
        .kpi-meta { white-space:normal; }
        .panel { padding:12px; }
        .cols, .col-axis { gap:3px; } .cols { height:100px; }
        .col-value { font-size:9px; } .col-axis { font-size:9px; }
        .quick-add span { display:none; } .quick-add { padding:8px; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <h1>{{ $greeting }}, {{ auth()->user()->username }}</h1>
            <p class="sub">Overview · updated {{ $generatedAt }}</p>
        </div>
    </div>
    <div class="top-actions">
        <a class="quick-add" href="{{ route('app-users.create') }}" aria-label="Add app user"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span>App user</span></a>
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="logout">Sign out</button></form>
    </div>
</header>

<div class="dash">
    {{-- KPIs --}}
    <div class="kpi-grid">
        <a class="kpi hero" href="{{ route('app-users.index') }}">
            <span class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 4.5a3 3 0 0 1 0 6M21 20a6 6 0 0 0-4-5.6"/></svg></span>
            <span class="kpi-body">
                <span class="kpi-label">Total app users</span>
                <span class="kpi-value">{{ $fmt($u['total']) }}</span>
                <span class="kpi-meta"><b>{{ $fmt($u['active']) }}</b> active · {{ $fmt($u['blocked']) }} blocked</span>
            </span>
        </a>
        <div class="kpi">
            <span class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1"/></svg></span>
            <span class="kpi-body">
                <span class="kpi-label">Online now</span>
                <span class="kpi-value"><span class="live-dot" aria-hidden="true"></span>{{ $fmt($u['onlineNow']) }}</span>
                <span class="kpi-meta">Active in last {{ $onlineWindowMinutes }} min</span>
            </span>
        </div>
        <div class="kpi">
            <span class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="8" r="3"/><path d="M4 20a6 6 0 0 1 11-3.3M18 14v6M15 17h6"/></svg></span>
            <span class="kpi-body">
                <span class="kpi-label">New this month</span>
                <span class="kpi-row"><span class="kpi-value">{{ $fmt($u['newThisMonth']) }}</span><span class="chip {{ $newState }}" title="Compared with the same days of last month">{!! $arrow($newState) !!}{{ $newText }}</span></span>
                <span class="kpi-meta">{{ $u['thisMonthLabel'] }}</span>
            </span>
        </div>
        <div class="kpi">
            <span class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg></span>
            <span class="kpi-body">
                <span class="kpi-label">Previous month</span>
                <span class="kpi-value">{{ $fmt($u['newPrevMonth']) }}</span>
                <span class="kpi-meta">New users · {{ $u['prevMonthLabel'] }}</span>
            </span>
        </div>
    </div>

    {{-- Growth --}}
    <div class="grid-2">
        <article class="panel">
            <div class="panel-head">
                <div><h3>New users per month</h3><p>Last 12 months</p></div>
                <div class="panel-figure">{{ $fmt($monthlyTotal) }}<small>total</small></div>
            </div>
            @if($monthlyTotal === 0)
                <div class="empty"><strong>No registrations yet</strong>New app users will appear here month by month.</div>
            @else
                <div role="img" aria-label="New app users per month for the last 12 months">
                    <div class="cols">
                        @foreach($monthlySignups as $m)
                            <div class="col {{ $m['current'] ? 'current' : '' }}" tabindex="0" data-tip="{{ $m['full'] }}{{ $m['current'] ? ' (so far)' : '' }}&#10;{{ $fmt($m['value']) }} new {{ \Illuminate\Support\Str::plural('user', $m['value']) }}">
                                <span class="col-value">{{ $m['value'] > 0 ? $fmt($m['value']) : '' }}</span>
                                <div class="col-bar" style="height:{{ $m['value'] > 0 ? max(2, $m['value'] / $monthlyMax * 85) : 0 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="col-axis" aria-hidden="true">@foreach($monthlySignups as $m)<span class="{{ $m['current'] ? 'current' : '' }}">{{ $m['label'] }}</span>@endforeach</div>
                </div>
                <details class="data-table">
                    <summary>View as table</summary>
                    <div class="table-wrap"><table><thead><tr><th>MONTH</th><th>NEW USERS</th></tr></thead><tbody>
                        @foreach(array_reverse($monthlySignups) as $m)<tr><td>{{ $m['full'] }}</td><td>{{ $fmt($m['value']) }}</td></tr>@endforeach
                    </tbody></table></div>
                </details>
            @endif
        </article>

        <article class="panel">
            <div class="panel-head"><div><h3>This month vs last month</h3><p>{{ $thisMonthShort }} compared with {{ $prevMonthShort }}</p></div></div>
            <div class="legend" aria-hidden="true"><span><i class="key"></i>{{ $thisMonthShort }}</span><span><i class="key prev"></i>{{ $prevMonthShort }}</span></div>
            <div class="compare">
                @foreach($compare as $row)
                    <div>
                        <div class="compare-head">
                            <span><strong>{{ $row['label'] }}</strong><small>{{ $row['hint'] }}</small></span>
                            <span class="chip {{ $row['state'] }}">{!! $arrow($row['state']) !!}{{ $row['text'] }}</span>
                        </div>
                        <div class="compare-row" tabindex="0" data-tip="{{ $row['label'] }} · {{ $u['thisMonthLabel'] }}&#10;{{ $fmt($row['now']) }}">
                            <span>{{ mb_substr($thisMonthShort, 0, 3) }}</span><div class="track"><div class="fill" style="width:{{ $row['now'] / $compareMax * 100 }}%"></div></div><b>{{ $fmt($row['now']) }}</b>
                        </div>
                        <div class="compare-row" tabindex="0" data-tip="{{ $row['label'] }} · {{ $u['prevMonthLabel'] }}&#10;{{ $fmt($row['prev']) }}">
                            <span>{{ mb_substr($prevMonthShort, 0, 3) }}</span><div class="track"><div class="fill prev" style="width:{{ $row['prev'] / $compareMax * 100 }}%"></div></div><b>{{ $fmt($row['prev']) }}</b>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    {{-- People & activity --}}
    <div class="grid-3">
        <article class="panel">
            <div class="panel-head"><div><h3>User breakdown</h3><p>Account status and devices</p></div></div>
            @if($u['total'] > 0)
                <div class="stackbar" role="img" aria-label="{{ $u['active'] }} active, {{ $u['inactive'] }} inactive, {{ $u['blocked'] }} blocked">
                    @foreach($statusRows as $s)
                        @if($s['value'] > 0)<div class="st-{{ $s['key'] }}" style="flex:{{ $s['value'] }}" tabindex="0" data-tip="{{ $s['label'] }}&#10;{{ $fmt($s['value']) }} users ({{ $pct($s['value'], $u['total']) }}%)"></div>@endif
                    @endforeach
                </div>
            @endif
            <ul class="status-list">
                @foreach($statusRows as $s)
                    <li>
                        <span class="status-name">
                            <span class="status-icon st-{{ $s['key'] }}" aria-hidden="true">
                                @if($s['key'] === 'active')<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6"><path d="m3.5 8.5 3 3 6-7"/></svg>
                                @elseif($s['key'] === 'inactive')<svg viewBox="0 0 16 16" fill="currentColor"><rect x="4" y="3" width="3" height="10" rx="1"/><rect x="9" y="3" width="3" height="10" rx="1"/></svg>
                                @else<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6"><path d="m4.5 4.5 7 7m0-7-7 7"/></svg>@endif
                            </span>{{ $s['label'] }}
                        </span>
                        <strong>{{ $fmt($s['value']) }}<small>{{ $pct($s['value'], $u['total']) }}%</small></strong>
                    </li>
                @endforeach
            </ul>
            <p class="sub-head">Devices</p>
            @if(empty($platforms))
                <div class="empty">No device data yet</div>
            @else
                <ul class="hbars">
                    @foreach($platforms as $p)
                        <li class="hbar" tabindex="0" data-tip="{{ $p['name'] }}&#10;{{ $fmt($p['value']) }} users ({{ $pct($p['value'], $platformTotal) }}%)">
                            <div class="hbar-top"><span class="hbar-name">{{ $p['name'] }}</span><span class="hbar-val"><b>{{ $fmt($p['value']) }}</b> · {{ $pct($p['value'], $platformTotal) }}%</span></div>
                            <div class="track"><div class="fill" style="width:{{ $p['value'] / $platformMax * 100 }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="panel">
            <div class="panel-head"><div><h3>Recently joined</h3><p>Newest app accounts</p></div><a class="link" href="{{ route('app-users.index') }}">View all</a></div>
            @if(empty($recentAppUsers))
                <div class="empty"><strong>No users yet</strong><a class="link" href="{{ route('app-users.create') }}">+ Add the first app user</a></div>
            @else
                <ul class="feed">
                    @foreach($recentAppUsers as $ru)
                        <li>
                            @if(!empty($ru['photo']))<img class="feed-avatar" src="{{ $ru['photo'] }}" alt="" loading="lazy" width="28" height="28" style="object-fit:cover;padding:0">@else<span class="feed-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($ru['name'], 0, 1)) }}</span>@endif
                            <span class="feed-main"><strong>{{ $ru['name'] }}</strong><span>{{ $ru['joined'] }}</span></span>
                            <span class="pill {{ $ru['status'] }}">{{ ucfirst($ru['status']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="panel">
            <div class="panel-head"><div><h3>Admin activity</h3><p>This console, today</p></div><a class="link" href="{{ route('access-logs') }}">Logs</a></div>
            <div class="mini-stats">
                <div><strong>{{ $fmt($admin['eventsToday']) }}</strong><span>Visits</span></div>
                <div><strong>{{ $fmt($admin['uniqueIpsToday']) }}</strong><span>Unique IPs</span></div>
                <div><strong>{{ $fmt($admin['activeAdmins']) }}</strong><span>Admins</span></div>
            </div>
            @if($recentLogs->isNotEmpty())
                <ul class="feed">
                    @foreach($recentLogs as $log)
                        <li>
                            <span class="feed-avatar" aria-hidden="true">{{ strtoupper(substr($log->adminUser?->username ?? 'G', 0, 1)) }}</span>
                            <span class="feed-main"><strong>/{{ ltrim($log->path, '/') }}</strong><span>{{ $log->adminUser?->username ?? 'Guest' }} · {{ $log->ip_address ?? '—' }} · {{ $log->browser ?? 'Other' }}</span></span>
                            <span class="feed-side">{{ $log->created_at?->diffForHumans(short: true) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </div>

    {{-- Coverage --}}
    <article class="panel">
        <div class="panel-head">
            <div><h3>Bangladesh coverage</h3><p>Administrative areas stored in GeoNexa · thanas per division</p></div>
            <a class="link" href="{{ route('locations.divisions') }}">Manage →</a>
        </div>
        <div class="coverage-body">
            <div class="cov-stats">
                <a class="cov-stat" href="{{ route('locations.divisions') }}"><span>Divisions</span><strong>{{ $fmt($loc['divisions']) }}</strong><small>বিভাগ</small></a>
                <a class="cov-stat" href="{{ route('locations.districts') }}"><span>Districts</span><strong>{{ $fmt($loc['districts']) }}</strong><small>জেলা</small></a>
                <a class="cov-stat" href="{{ route('locations.upazilas') }}"><span>Thanas</span><strong>{{ $fmt($loc['upazilas']) }}</strong><small>থানা / উপজেলা</small></a>
            </div>
            @if(empty($loc['byDivision']))
                <div class="empty"><strong>No location data</strong>Run the Bangladesh location seeder to load divisions.</div>
            @else
                <ul class="hbars cov-bars">
                    @foreach($loc['byDivision'] as $d)
                        <li class="hbar" tabindex="0" data-tip="{{ $d['name'] }} ({{ $d['name_bn'] }})&#10;{{ $fmt($d['districts']) }} districts · {{ $fmt($d['upazilas']) }} thanas">
                            <div class="hbar-top"><span class="hbar-name">{{ $d['name'] }}<small>{{ $d['name_bn'] }}</small></span><span class="hbar-val"><b>{{ $fmt($d['upazilas']) }}</b> · {{ $d['districts'] }} dist.</span></div>
                            <div class="track"><div class="fill" style="width:{{ $d['upazilas'] / $divisionMax * 100 }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </article>
</div>

<div class="viz-tip" id="viz-tip" role="tooltip" aria-hidden="true"></div>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const tip = document.getElementById('viz-tip');
    const show = (text, x, y) => {
        tip.textContent = text;
        tip.classList.add('show');
        const pad = 12, w = tip.offsetWidth, h = tip.offsetHeight;
        let left = x + pad, top = y - h - pad;
        if (left + w > window.innerWidth - 8) left = x - w - pad;
        tip.style.left = Math.max(8, left) + 'px';
        tip.style.top = (top < 8 ? y + pad : top) + 'px';
    };
    const hide = () => tip.classList.remove('show');
    document.querySelectorAll('[data-tip]').forEach((el) => {
        el.addEventListener('pointermove', (e) => show(el.dataset.tip, e.clientX, e.clientY));
        el.addEventListener('pointerleave', hide);
        el.addEventListener('focus', () => { const r = el.getBoundingClientRect(); show(el.dataset.tip, r.left + r.width / 2, r.top); });
        el.addEventListener('blur', hide);
    });
    window.addEventListener('scroll', hide, { passive: true });
})();
</script>
@endpush
