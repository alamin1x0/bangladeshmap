@extends('layouts.app')

@push('head')
<style>
    .n-add { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:9px; background:var(--primary); color:#fff; font-weight:700; font-size:12px; text-decoration:none; white-space:nowrap; box-shadow:0 5px 12px #2563eb30; }
    .n-add:hover { filter:brightness(1.08); } .n-add svg { width:14px; height:14px; }
    .n-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
    .n-stat { display:flex; align-items:center; gap:11px; padding:12px 14px; background:var(--surface); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow); }
    .n-stat .ic { display:grid; place-items:center; width:34px; height:34px; flex:0 0 34px; border-radius:10px; background:var(--primary-soft); color:var(--primary); }
    .n-stat .ic svg { width:17px; height:17px; }
    .n-stat .ic.good { background:#e6f6ea; color:#00720a; } .n-stat .ic.bad { background:#fdecec; color:#d03b3b; }
    [data-theme="dark"] .n-stat .ic.good { background:#123222; color:#3ccf3c; } [data-theme="dark"] .n-stat .ic.bad { background:#3a1b1f; color:#ff8a8a; }
    .n-stat strong { display:block; font-size:19px; letter-spacing:-.4px; line-height:1.2; font-variant-numeric:tabular-nums; }
    .n-stat span { color:var(--muted); font-size:11.5px; }
    .n-msg strong { display:block; font-size:12.5px; max-width:380px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .n-msg span { display:block; color:var(--muted); font-size:11.5px; max-width:380px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .n-row { cursor:pointer; }
    .n-aud { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; background:var(--surface-2); border:1px solid var(--line); font-size:11px; font-weight:600; white-space:nowrap; }
    .n-result { min-width:130px; }
    .n-result .bar { display:flex; height:6px; border-radius:3px; overflow:hidden; background:var(--surface-2); margin-bottom:4px; }
    .n-result .bar i { display:block; } .n-result .ok { background:#0ca30c; } .n-result .ko { background:#d03b3b; }
    .n-result small { font-size:11px; color:var(--muted); white-space:nowrap; } .n-result small b { color:var(--ink); }
    .st { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
    .st .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .st.sent { background:#e6f6ea; color:#00720a; } .st.partial { background:#fff4dc; color:#8a5a00; } .st.failed { background:#fdecec; color:#c62828; } .st.sending { background:var(--primary-soft); color:var(--primary); }
    [data-theme="dark"] .st.sent { background:#123222; color:#3ccf3c; } [data-theme="dark"] .st.partial { background:#3a2e10; color:#fab219; } [data-theme="dark"] .st.failed { background:#3a1b1f; color:#ff8a8a; }
    .n-empty { display:grid; place-items:center; gap:6px; padding:44px 16px; text-align:center; color:var(--muted); font-size:12.5px; }
    .n-empty .ico { display:grid; place-items:center; width:50px; height:50px; border-radius:15px; background:var(--primary-soft); color:var(--primary); margin-bottom:4px; }
    .n-empty .ico svg { width:24px; height:24px; } .n-empty strong { color:var(--ink); font-size:14px; }
    .n-pager { display:flex; justify-content:space-between; align-items:center; padding:11px 16px; border-top:1px solid var(--line); font-size:12px; color:var(--muted); }
    .n-pager nav { display:flex; gap:6px; } .n-pager a, .n-pager span.off { padding:5px 10px; border:1px solid var(--line); border-radius:8px; color:var(--ink); text-decoration:none; font-weight:600; } .n-pager span.off { opacity:.45; }
    .n-banner { display:flex; gap:9px; padding:11px 13px; border-radius:11px; background:#fff6e0; color:#7a5200; font-size:12.5px; margin-bottom:12px; }
    .n-banner svg { width:17px; height:17px; flex:0 0 17px; margin-top:1px; } [data-theme="dark"] .n-banner { background:#3a2e10; color:#f5c45a; }
    @media (max-width:1000px) { .n-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:650px) {
        .n-add span { display:none; } .n-add { padding:8px; }
        .n-msg strong, .n-msg span { max-width:none; white-space:normal; }
        .table-wrap.stacked td[data-label="MESSAGE"]::before { content:none; }
        .table-wrap.stacked td[data-label="MESSAGE"] { justify-content:flex-start; text-align:left; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div><p class="eyebrow">People</p><h1>Notifications</h1><p class="sub">Push messages sent to app users’ phones.</p></div>
    </div>
    <div class="top-actions">
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
        <a class="n-add" href="{{ route('notifications.create') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg><span>Send notification</span></a>
    </div>
</header>

@if(! $configured)
    <div class="n-banner" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17v.5"/></svg><div><strong>Firebase is not configured.</strong> Set FIREBASE_CREDENTIALS in .env.</div></div>
@elseif($stats['reachable'] === 0)
    <div class="n-banner" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg><div><strong>No device tokens yet.</strong> Once the mobile app saves each user’s <code>fcm_token</code>, they can receive notifications. You can already send to a single device using <b>Test token</b>.</div></div>
@endif

<div class="n-stats">
    <div class="n-stat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></span><div><strong>{{ number_format($stats['sent']) }}</strong><span>Notifications sent</span></div></div>
    <div class="n-stat"><span class="ic good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span><div><strong>{{ number_format($stats['delivered']) }}</strong><span>Delivered</span></div></div>
    <div class="n-stat"><span class="ic bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg></span><div><strong>{{ number_format($stats['failed']) }}</strong><span>Failed</span></div></div>
    <div class="n-stat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg></span><div><strong>{{ number_format($stats['reachable']) }}</strong><span>Reachable devices</span></div></div>
</div>

<section class="table-card">
    <div class="table-head"><div><h2>History</h2><p class="sub">Newest first</p></div></div>
    @if($notifications->isEmpty())
        <div class="n-empty">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></span>
            <strong>No notifications sent yet</strong>
            <span>Send your first push message to app users.</span>
            <a class="n-add" href="{{ route('notifications.create') }}" style="margin-top:6px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg><span>Send notification</span></a>
        </div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>MESSAGE</th><th>AUDIENCE</th><th>RESULT</th><th>STATUS</th><th>SENT</th><th></th></tr></thead>
            <tbody>
            @foreach($notifications as $n)
                @php $total = max(1, $n->success_count + $n->failure_count); @endphp
                <tr class="n-row" data-href="{{ route('notifications.show', $n) }}">
                    <td class="n-msg"><strong>{{ $n->title }}</strong><span>{{ $n->body }}</span></td>
                    <td><span class="n-aud">{{ $n->audienceLabel() }}</span></td>
                    <td class="n-result">
                        <div class="bar"><i class="ok" style="width:{{ $n->success_count / $total * 100 }}%"></i><i class="ko" style="width:{{ $n->failure_count / $total * 100 }}%"></i></div>
                        <small><b>{{ number_format($n->success_count) }}</b> delivered{{ $n->failure_count ? ' · '.number_format($n->failure_count).' failed' : '' }}</small>
                    </td>
                    <td><span class="st {{ $n->status }}"><i class="dot"></i>{{ ['sent' => 'Sent', 'partial' => 'Partly sent', 'failed' => 'Failed', 'sending' => 'Sending'][$n->status] ?? ucfirst($n->status) }}</span></td>
                    <td><span title="{{ $n->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}">{{ $n->created_at->diffForHumans() }}</span><small style="display:block;color:var(--muted);font-size:11px">by {{ $n->sender?->username ?? '—' }}</small></td>
                    <td style="text-align:right"><a class="link" href="{{ route('notifications.show', $n) }}" style="color:var(--primary);font-weight:700;font-size:12px;text-decoration:none">Details →</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="n-pager">
            <span>Showing {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ number_format($notifications->total()) }}</span>
            @if($notifications->hasPages())
                <nav>@if($notifications->onFirstPage())<span class="off">‹ Prev</span>@else<a href="{{ $notifications->previousPageUrl() }}">‹ Prev</a>@endif @if($notifications->hasMorePages())<a href="{{ $notifications->nextPageUrl() }}">Next ›</a>@else<span class="off">Next ›</span>@endif</nav>
            @endif
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
    document.querySelectorAll('.n-row').forEach((row) => row.addEventListener('click', (e) => { if (!e.target.closest('a')) window.location = row.dataset.href; }));
</script>
@endpush
