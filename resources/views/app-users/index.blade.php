@extends('layouts.app')

@php
    $chips = [
        null => ['label' => 'All users', 'short' => 'All', 'class' => 'all'],
        'active' => ['label' => 'Active', 'short' => 'Active', 'class' => 'active'],
        'inactive' => ['label' => 'Inactive', 'short' => 'Inactive', 'class' => 'inactive'],
        'blocked' => ['label' => 'Blocked', 'short' => 'Blocked', 'class' => 'blocked'],
    ];
    $chipUrl = fn ($value) => route('app-users.index', array_filter(['q' => $search ?: null, 'status' => $value]));
    $avatarTones = ['#2a78d6', '#1baf7a', '#6250d6', '#eb6834', '#e87ba4', '#008300'];
    $tone = fn ($name) => $avatarTones[crc32((string) $name) % count($avatarTones)];
@endphp

@push('head')
<style>
    .page-flash { display:flex; align-items:center; gap:9px; margin-bottom:12px; padding:10px 13px; border-radius:11px; background:#e9f8ef; color:#146c3b; font-size:12.5px; font-weight:600; }
    .page-flash svg { width:16px; height:16px; flex:0 0 16px; }
    [data-theme="dark"] .page-flash { background:#123222; color:#69d998; }

    .add-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:9px; background:var(--primary); color:#fff; font-weight:700; font-size:12px; text-decoration:none; white-space:nowrap; box-shadow:0 5px 12px #2563eb30; }
    .add-btn:hover { filter:brightness(1.08); } .add-btn svg { width:13px; height:13px; }

    .toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid var(--line); flex-wrap:wrap; }
    .filter-chips { display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; flex-wrap:nowrap; min-width:0; }
    .filter-chips::-webkit-scrollbar { display:none; }
    .fchip { display:inline-flex; align-items:center; gap:6px; flex:0 0 auto; padding:5px 11px 5px 9px; border:1px solid var(--line); border-radius:999px; background:var(--surface); color:var(--muted); font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; transition:.15s; }
    .fchip:hover { border-color:var(--primary); color:var(--ink); }
    .fchip .dot { width:7px; height:7px; flex:0 0 7px; border-radius:50%; background:var(--primary); }
    .fchip.active .dot { background:#0ca30c; } .fchip.inactive .dot { background:#fab219; } .fchip.blocked .dot { background:#d03b3b; }
    .fchip b { color:var(--ink); font-variant-numeric:tabular-nums; }
    .fchip.on { background:var(--ink); border-color:var(--ink); color:var(--surface); }
    .fchip.on b { color:var(--surface); }

    .search-box { position:relative; display:flex; align-items:center; flex:0 1 280px; min-width:0; }
    .search-box svg { position:absolute; left:10px; width:15px; height:15px; color:var(--muted); pointer-events:none; }
    .search-box input { width:100%; padding:7px 30px 7px 32px; border:1px solid var(--line); border-radius:9px; background:var(--surface); color:var(--ink); font-size:12.5px; }
    .search-box input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px #2563eb22; }
    .search-box .clear { position:absolute; right:6px; display:grid; place-items:center; width:22px; height:22px; border-radius:6px; color:var(--muted); text-decoration:none; font-size:15px; }
    .search-box .clear:hover { background:var(--surface-2); color:var(--ink); }

    .uid { display:inline-block; padding:2px 7px; border-radius:6px; background:var(--surface-2); border:1px solid var(--line); font:700 11px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--muted); white-space:nowrap; }
    .u-person { display:flex; align-items:center; gap:10px; min-width:0; }
    .u-avatar { display:grid; place-items:center; width:40px; height:40px; flex:0 0 40px; border-radius:12px; color:#fff; font-weight:800; font-size:12.5px; object-fit:cover; background:var(--surface-2); }
    .u-name { font-weight:700; font-size:12.5px; } .u-contact { display:flex; flex-direction:column; align-items:flex-start; gap:1px; color:var(--muted); font-size:11.5px; line-height:1.4; white-space:nowrap; min-width:0; }
    .u-mail { display:block; max-width:100%; overflow:hidden; text-overflow:ellipsis; min-width:0; }
    .u-sep { opacity:.6; }
    .u-phone { display:inline-flex; align-items:center; gap:3px; flex:0 0 auto; font-variant-numeric:tabular-nums; }
    .u-phone svg { width:11px; height:11px; }
    .u-name { display:block; }
    .s-pill { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
    .s-pill .dot { width:6px; height:6px; border-radius:50%; }
    .s-pill.active { background:#e6f6ea; color:#00720a; } .s-pill.active .dot { background:#0ca30c; }
    .s-pill.inactive { background:#fff4dc; color:#8a5a00; } .s-pill.inactive .dot { background:#fab219; }
    .s-pill.blocked { background:#fdecec; color:#c62828; } .s-pill.blocked .dot { background:#d03b3b; }
    [data-theme="dark"] .s-pill.active { background:#123222; color:#3ccf3c; } [data-theme="dark"] .s-pill.inactive { background:#3a2e10; color:#fab219; } [data-theme="dark"] .s-pill.blocked { background:#3a1b1f; color:#ff8a8a; }
    .app-cell { font-size:12px; } .app-cell small { display:block; color:var(--muted); font-size:11px; }
    .plat { display:inline-flex; align-items:center; gap:5px; font-weight:600; } .plat svg { width:13px; height:13px; color:var(--muted); }
    .muted { color:var(--muted); }
    .log-link { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; background:var(--primary-soft); color:var(--primary); font-size:11px; font-weight:700; text-decoration:none; }
    .log-link:hover { filter:brightness(.97); }
    .row-actions { display:flex; gap:4px; justify-content:flex-end; }
    .act { display:grid; place-items:center; width:30px; height:30px; border:1px solid var(--line); border-radius:8px; background:var(--surface); color:var(--muted); cursor:pointer; text-decoration:none; transition:.15s; }
    .act svg { width:15px; height:15px; }
    .act:hover { color:var(--primary); border-color:var(--primary); }
    .act.act-muted { opacity:.45; }
    .act.danger:hover { color:#d03b3b; border-color:#d03b3b; background:#fdecec; }
    [data-theme="dark"] .act.danger:hover { background:#3a1b1f; }
    .row-actions form { margin:0; }

    .empty-state { display:grid; place-items:center; gap:6px; padding:40px 16px; text-align:center; color:var(--muted); font-size:12.5px; }
    .empty-state .ico { display:grid; place-items:center; width:46px; height:46px; border-radius:14px; background:var(--primary-soft); color:var(--primary); margin-bottom:4px; }
    .empty-state .ico svg { width:22px; height:22px; } .empty-state strong { color:var(--ink); font-size:14px; }

    .pager { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:11px 16px; border-top:1px solid var(--line); font-size:12px; color:var(--muted); }
    .pager nav { display:flex; gap:6px; }
    .pager a, .pager span.disabled { display:inline-flex; align-items:center; gap:4px; padding:5px 10px; border:1px solid var(--line); border-radius:8px; color:var(--ink); text-decoration:none; font-weight:600; }
    .pager a:hover { border-color:var(--primary); color:var(--primary); }
    .pager span.disabled { opacity:.45; }

    @media (max-width:650px) {
        .toolbar { padding:11px 12px; }
        .search-box { flex:1 1 100%; } .search-box input { font-size:16px; }
        .add-btn span { display:none; } .add-btn { padding:8px; }
        .fchip { padding:4px 9px 4px 8px; font-size:11.5px; }
        .fchip .long { display:none; }
        .table-wrap.stacked td .u-person { justify-content:flex-end; text-align:right; }
        .table-wrap.stacked td[data-label="USER"]::before { content:none; }
        .table-wrap.stacked td[data-label="USER"] { justify-content:flex-start; }
        .table-wrap.stacked td[data-label="USER"] .u-person { justify-content:flex-start; text-align:left; }
        .table-wrap.stacked td[data-label=""]::before { content:none; }
        .table-wrap.stacked td[data-label=""] { justify-content:flex-end; }
        .pager { padding:10px 12px; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div><p class="eyebrow">People</p><h1>App Users</h1><p class="sub">Mobile app accounts, status and devices.</p></div>
    </div>
    <div class="top-actions">
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
        <a class="add-btn" href="{{ route('app-users.create') }}" aria-label="Add app user"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span>Add app user</span></a>
    </div>
</header>

@if(session('success'))
    <div class="page-flash" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>{{ session('success') }}</div>
@endif

<section class="table-card">
    <div class="toolbar">
        <nav class="filter-chips" aria-label="Filter by status">
            @foreach($chips as $value => $chip)
                @php $key = $value ?: 'all'; $on = ($status ?? null) === ($value ?: null); @endphp
                <a class="fchip {{ $chip['class'] }} {{ $on ? 'on' : '' }}" href="{{ $chipUrl($value ?: null) }}" @if($on) aria-current="true" @endif>
                    <i class="dot"></i>{{ $chip['short'] }}@if($key === 'all')<span class="long"> users</span>@endif <b>{{ number_format($counts[$key]) }}</b>
                </a>
            @endforeach
        </nav>
        <form method="get" class="search-box" role="search">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search ID, name, email or phone" aria-label="Search app users" maxlength="100">
            @if($search)<a class="clear" href="{{ route('app-users.index', array_filter(['status' => $status])) }}" aria-label="Clear search">×</a>@endif
        </form>
    </div>

    @if($users->isEmpty())
        <div class="empty-state">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 4.5a3 3 0 0 1 0 6M21 20a6 6 0 0 0-4-5.6"/></svg></span>
            @if($search || $status)
                <strong>No users match this filter</strong>
                <span>Try a different search or <a class="link" href="{{ route('app-users.index') }}" style="color:var(--primary);font-weight:700">show all users</a>.</span>
            @else
                <strong>No app users yet</strong>
                <span>Accounts created in the mobile app, or added here, will appear in this list.</span>
                <a class="add-btn" href="{{ route('app-users.create') }}" style="margin-top:6px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span>Add the first user</span></a>
            @endif
        </div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>ID</th><th>USER</th><th>ACCOUNT</th><th>STATUS</th><th>APP</th><th>LAST LOGIN</th><th>ACTIVITY</th><th style="text-align:right"></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td><span class="uid" title="App user ID">#{{ $user->id }}</span></td>
                    <td>
                        <div class="u-person">
                            @if($user->photo_url)
                                <img class="u-avatar" src="{{ $user->photo_url }}" alt="" loading="lazy" width="40" height="40">
                            @else
                                <span class="u-avatar" style="background:{{ $tone($user->name) }}" aria-hidden="true">{{ $user->initials }}</span>
                            @endif
                            <div style="min-width:0"><span class="u-name">{{ $user->name }}</span><span class="u-contact"><span class="u-mail">{{ $user->email }}</span>@if($user->phone)<span class="u-phone"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>{{ $user->phone }}</span>@endif</span></div>
                        </div>
                    </td>
                    <td><span class="s-pill {{ $user->account_type === 'paid' ? 'active' : ($user->account_type === 'trial' ? 'inactive' : '') }}"><i class="dot"></i>{{ strtoupper($user->account_type) }}</span>@if($user->subscription_package_name)<small class="muted">{{ $user->subscription_package_name }}</small>@endif</td>
                    <td><span class="s-pill {{ $user->status }}"><i class="dot"></i>{{ ucfirst($user->status) }}</span></td>
                    <td class="app-cell">
                        @if($user->platform || $user->app_version)
                            <span class="plat">
                                @if(strcasecmp((string) $user->platform, 'ios') === 0)<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.4 12.6c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.2-2.8.8-3.5.8-.7 0-1.8-.8-3-.8-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.3.9-1.3 1.3-2.5 1.3-2.6 0 0-2.5-1-2.5-3.7zM14.2 5.8c.6-.8 1.1-1.8 1-2.8-.9 0-2 .6-2.7 1.4-.6.7-1.1 1.7-1 2.7 1 .1 2-.5 2.7-1.3z"/></svg>
                                @elseif($user->platform)<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg>@endif
                                {{ $user->platform ?: 'Unknown' }}
                            </span>
                            <small>{{ $user->app_version ? 'v'.ltrim($user->app_version, 'vV') : 'version —' }}</small>
                        @else
                            <span class="muted">Not reported</span>
                        @endif
                    </td>
                    <td>
                        @if($user->last_login_at)
                            <span title="{{ $user->last_login_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}">{{ $user->last_login_at->diffForHumans() }}</span>
                        @else
                            <span class="muted">Never</span>
                        @endif
                    </td>
                    <td><a class="log-link" href="{{ route('app-users.logs', $user) }}" title="View activity logs">{{ number_format($user->logs_count) }} {{ \Illuminate\Support\Str::plural('log', $user->logs_count) }}</a></td>
                    <td>
                        <div class="row-actions">
                            <a class="act {{ ($user->status === 'active' && filled($user->fcm_token)) ? '' : 'act-muted' }}" href="{{ route('notifications.create', ['user' => $user->id]) }}" title="{{ ($user->status === 'active' && filled($user->fcm_token)) ? 'Send notification' : 'Send notification (no device token yet)' }}" aria-label="Send notification to {{ $user->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></a>
                            <a class="act" href="{{ route('app-users.logs', $user) }}" title="Activity logs" aria-label="Activity logs for {{ $user->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 8v4l2.5 2.5"/></svg></a>
                            <a class="act" href="{{ route('app-users.edit', $user) }}" title="Edit" aria-label="Edit {{ $user->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/></svg></a>
                            <form method="post" action="{{ route('app-users.destroy', $user) }}" data-confirm-title="Delete this app user?" data-confirm="The account will be removed permanently and the user can no longer sign in. Their activity logs are kept for auditing." data-confirm-item="{{ $user->name }} · {{ $user->email }}" data-confirm-button="Delete user">@csrf @method('DELETE')
                                <button type="submit" class="act danger" title="Delete" aria-label="Delete {{ $user->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>

        <div class="pager">
            <span>Showing {{ number_format($users->firstItem()) }}–{{ number_format($users->lastItem()) }} of {{ number_format($users->total()) }}</span>
            @if($users->hasPages())
                <nav aria-label="Pagination">
                    @if($users->onFirstPage())<span class="disabled">‹ Prev</span>@else<a href="{{ $users->previousPageUrl() }}" rel="prev">‹ Prev</a>@endif
                    @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}" rel="next">Next ›</a>@else<span class="disabled">Next ›</span>@endif
                </nav>
            @endif
        </div>
    @endif
</section>
@endsection
