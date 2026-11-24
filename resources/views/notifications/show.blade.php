@extends('layouts.app')

@php
    $n = $notification;
    $total = max(1, $n->success_count + $n->failure_count);
    $statusText = ['sent' => 'Sent', 'partial' => 'Partly sent', 'failed' => 'Failed', 'sending' => 'Sending'][$n->status] ?? ucfirst($n->status);
    $tones = ['#2a78d6', '#1baf7a', '#6250d6', '#eb6834', '#e87ba4', '#008300'];
    $tone = fn ($name) => $tones[crc32((string) $name) % count($tones)];
@endphp

@push('head')
<style>
    .flash { display:flex; align-items:center; gap:9px; margin-bottom:12px; padding:10px 13px; border-radius:11px; font-size:12.5px; font-weight:600; }
    .flash svg { width:16px; height:16px; flex:0 0 16px; }
    .flash.ok { background:#e9f8ef; color:#146c3b; } .flash.warn { background:#fff6e0; color:#7a5200; } .flash.err { background:#fdecec; color:#b42318; }
    [data-theme="dark"] .flash.ok { background:#123222; color:#69d998; } [data-theme="dark"] .flash.warn { background:#3a2e10; color:#f5c45a; } [data-theme="dark"] .flash.err { background:#3a1b1f; color:#ff9a9a; }
    .d-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:12px; align-items:start; margin-bottom:12px; }
    .d-card { background:var(--surface); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow); padding:16px; display:grid; gap:12px; }
    .d-card h3 { margin:0; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }
    .d-msg strong { display:block; font-size:16px; } .d-msg p { margin:4px 0 0; color:var(--ink); font-size:13px; line-height:1.55; white-space:pre-line; }
    .d-msg img { display:block; max-width:100%; max-height:200px; border-radius:10px; margin-top:10px; object-fit:cover; }
    .d-meta { display:grid; margin:0; }
    .d-meta div { display:flex; justify-content:space-between; gap:10px; padding:7px 0; font-size:12px; }
    .d-meta div + div { border-top:1px dashed var(--line); }
    .d-meta dt { color:var(--muted); } .d-meta dd { margin:0; font-weight:600; text-align:right; word-break:break-all; }
    .d-big { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .d-big div { padding:10px; border-radius:10px; background:var(--surface-2); }
    .d-big strong { display:block; font-size:20px; font-variant-numeric:tabular-nums; } .d-big span { color:var(--muted); font-size:11px; }
    .d-big .ok strong { color:#0ca30c; } .d-big .ko strong { color:#d03b3b; }
    .bar { display:flex; height:8px; border-radius:4px; overflow:hidden; background:var(--surface-2); } .bar i { display:block; } .bar .ok { background:#0ca30c; } .bar .ko { background:#d03b3b; }
    .st { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
    .st .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .st.sent { background:#e6f6ea; color:#00720a; } .st.partial { background:#fff4dc; color:#8a5a00; } .st.failed { background:#fdecec; color:#c62828; } .st.sending { background:var(--primary-soft); color:var(--primary); }
    [data-theme="dark"] .st.sent { background:#123222; color:#3ccf3c; } [data-theme="dark"] .st.partial { background:#3a2e10; color:#fab219; } [data-theme="dark"] .st.failed { background:#3a1b1f; color:#ff8a8a; }
    .r-user { display:flex; align-items:center; gap:9px; }
    .r-user .av { display:grid; place-items:center; width:30px; height:30px; flex:0 0 30px; border-radius:9px; color:#fff; font-weight:800; font-size:12px; object-fit:cover; }
    .r-user strong { display:block; font-size:12.5px; } .r-user span { display:block; color:var(--muted); font-size:11px; }
    .r-err { color:#c62828; font-size:11.5px; max-width:380px; white-space:normal; }
    .mono { font:600 11px ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--muted); }
    .again { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:9px; background:var(--primary); color:#fff; font-weight:700; font-size:12px; text-decoration:none; white-space:nowrap; }
    .again svg { width:14px; height:14px; }
    @media (max-width:980px) { .d-grid { grid-template-columns:minmax(0,1fr); } }
    @media (max-width:650px) {
        .again span { display:none; } .again { padding:8px; }
        .table-wrap.stacked td[data-label="RECIPIENT"]::before { content:none; }
        .table-wrap.stacked td[data-label="RECIPIENT"] { justify-content:flex-start; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <a class="page-back" href="{{ route('notifications.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 6-6 6 6 6"/></svg>Notifications</a>
            <h1>Notification #{{ $n->id }}</h1>
            <p class="sub">{{ $n->audienceLabel() }} · {{ $n->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</p>
        </div>
    </div>
    <div class="top-actions">
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
        <a class="again" href="{{ route('notifications.create') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg><span>New notification</span></a>
    </div>
</header>

@if(session('success'))<div class="flash ok" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>{{ session('success') }}</div>@endif
@if(session('warning'))<div class="flash warn" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17v.5"/></svg>{{ session('warning') }}</div>@endif
@error('send')<div class="flash err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>{{ $message }}</div>@enderror

<div class="d-grid">
    <div class="d-card d-msg">
        <h3>Message</h3>
        <div><strong>{{ $n->title }}</strong><p>{{ $n->body }}</p>@if($n->imageSrc())<img src="{{ $n->imageSrc() }}" alt="Notification image" loading="lazy" referrerpolicy="no-referrer">@endif</div>
    </div>
    <div class="d-card">
        <h3>Delivery</h3>
        <span><span class="st {{ $n->status }}"><i class="dot"></i>{{ $statusText }}</span></span>
        <div class="bar"><i class="ok" style="width:{{ $n->success_count / $total * 100 }}%"></i><i class="ko" style="width:{{ $n->failure_count / $total * 100 }}%"></i></div>
        <div class="d-big"><div class="ok"><strong>{{ number_format($n->success_count) }}</strong><span>Delivered</span></div><div class="ko"><strong>{{ number_format($n->failure_count) }}</strong><span>Failed</span></div></div>
        <dl class="d-meta">
            <div><dt>Audience</dt><dd>{{ $n->audienceLabel() }}{{ $n->audience === 'token' && $n->audience_detail ? ' '.$n->audience_detail : '' }}</dd></div>
            <div><dt>Sent by</dt><dd>{{ $n->sender?->username ?? '—' }}</dd></div>
            @if($n->link)<div><dt>Link</dt><dd>{{ $n->link }}</dd></div>@endif
        </dl>
    </div>
</div>

<section class="table-card">
    <div class="table-head"><div><h2>Recipients</h2><p class="sub">Failed deliveries are listed first</p></div></div>
    <div class="table-wrap"><table>
        <thead><tr><th>RECIPIENT</th><th>STATUS</th><th>DETAIL</th><th>TOKEN</th></tr></thead>
        <tbody>
        @forelse($recipients as $r)
            <tr>
                <td>
                    @if($r->appUser)
                        <a class="r-user" href="{{ route('app-users.edit', $r->appUser) }}" style="color:inherit;text-decoration:none">
                            @if($r->appUser->photo_url)<img class="av" src="{{ $r->appUser->photo_url }}" alt="" loading="lazy">@else<span class="av" style="background:{{ $tone($r->appUser->name) }}">{{ $r->appUser->initials }}</span>@endif
                            <span><strong>{{ $r->appUser->name }} <span style="display:inline;font-weight:600">#{{ $r->appUser->id }}</span></strong><span>{{ $r->appUser->email }}</span></span>
                        </a>
                    @else
                        <span class="r-user"><span class="av" style="background:#8592a8">T</span><span><strong>{{ $n->audience === 'token' ? 'Test device' : 'Deleted user' }}</strong></span></span>
                    @endif
                </td>
                <td><span class="st {{ $r->status === 'sent' ? 'sent' : 'failed' }}"><i class="dot"></i>{{ $r->status === 'sent' ? 'Delivered' : 'Failed' }}</span></td>
                <td>@if($r->error)<span class="r-err">{{ $r->error }}</span>@else<span class="mono" title="{{ $r->message_id }}">{{ $r->message_id ? \Illuminate\Support\Str::afterLast($r->message_id, '/') : '—' }}</span>@endif</td>
                <td><span class="mono">{{ $r->token_hint }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4">No recipients recorded.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($recipients->hasPages())
        <div style="display:flex;justify-content:flex-end;gap:6px;padding:11px 16px;border-top:1px solid var(--line)">
            @if(!$recipients->onFirstPage())<a class="btn" href="{{ $recipients->previousPageUrl() }}">‹ Prev</a>@endif
            @if($recipients->hasMorePages())<a class="btn" href="{{ $recipients->nextPageUrl() }}">Next ›</a>@endif
        </div>
    @endif
</section>
@endsection
