@extends('layouts.app')
@section('content')
@php
    $icons = ['district' => '#2563eb', 'upazila' => '#7c3aed', 'location_set' => '#0d9488', 'app_user' => '#d97706', 'landing_order' => '#db2777'];
    $total = $counts->sum();
@endphp
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div><p class="eyebrow">Audit trail</p><h1>Deleted data</h1><p class="sub">{{ number_format($total) }} deletions recorded · who deleted what, when, and the data as it was. Entries cannot be edited or removed.</p></div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

<nav class="dl-chips" aria-label="Filter by type">
    <a class="dl-chip {{ $type === '' ? 'on' : '' }}" href="{{ route('deletion-logs.index', array_filter(['q' => $search, 'from' => $from, 'to' => $to])) }}"><strong>{{ $total }}</strong> All</a>
    @foreach(\App\Models\DeletionLog::TYPES as $key => $label)
        <a class="dl-chip {{ $type === $key ? 'on' : '' }}" style="--c:{{ $icons[$key] }}" href="{{ route('deletion-logs.index', array_filter(['type' => $key, 'q' => $search, 'from' => $from, 'to' => $to])) }}"><i></i><strong>{{ $counts[$key] ?? 0 }}</strong> {{ $label }}</a>
    @endforeach
</nav>

<section class="table-card">
    <form method="get" class="dl-filters">
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        <input type="search" name="q" value="{{ $search }}" placeholder="Search name, code, parent or admin…">
        <label>From <input type="date" name="from" value="{{ $from }}"></label>
        <label>To <input type="date" name="to" value="{{ $to }}"></label>
        <button class="loc-btn" type="submit">Filter</button>
        @if($search || $from || $to || $type)<a class="loc-btn ghost" href="{{ route('deletion-logs.index') }}">Clear</a>@endif
    </form>

    @if($logs->isEmpty())
        <div class="dl-empty"><strong>No deleted data found</strong><span>Deletions made by admins will appear here.</span></div>
    @else
    <div class="table-wrap"><table class="loc-table">
        <thead><tr><th>When</th><th>Type</th><th>Deleted item</th><th>Belonged to</th><th>Deleted by</th><th>IP</th><th></th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td><strong>{{ $log->created_at->format('d M Y') }}</strong><small class="parent-sub">{{ $log->created_at->format('h:i A') }} · {{ $log->created_at->diffForHumans() }}</small></td>
                <td><span class="dl-badge" style="--c:{{ $icons[$log->entity_type] ?? '#64748b' }}">{{ $log->typeLabel() }}</span></td>
                <td><strong>{{ $log->entity_name ?: '—' }}</strong>@if($log->entity_code)<small class="parent-sub">{{ $log->entity_code }}</small>@endif @if($log->children_count)<small class="parent-sub">+ {{ $log->children_count }} thana(s) deleted with it</small>@endif</td>
                <td>{{ $log->parent_name ?: '—' }}</td>
                <td>{{ $log->admin_name ?: 'Unknown' }}</td>
                <td><small>{{ $log->ip_address ?: '—' }}</small></td>
                <td class="act-cell"><button type="button" class="act-edit" data-dl-open="{{ $log->id }}">Details</button></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @if($logs->hasPages())
    <div class="dl-pager"><span>Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }} · {{ number_format($logs->total()) }} entries</span>
        @if($logs->onFirstPage())<span class="off">‹ Prev</span>@else<a href="{{ $logs->previousPageUrl() }}">‹ Prev</a>@endif
        @if($logs->hasMorePages())<a href="{{ $logs->nextPageUrl() }}">Next ›</a>@else<span class="off">Next ›</span>@endif
    </div>
    @endif
    @endif
</section>

<div class="dm" id="dl-modal" hidden role="dialog" aria-modal="true" aria-labelledby="dl-title" style="position:fixed;inset:0;z-index:120;display:none;place-items:center;padding:20px;background:#071127b8;backdrop-filter:blur(6px)">
    <div class="dl-card">
        <button type="button" class="dl-x" data-dl-close aria-label="Close">&times;</button>
        <h2 id="dl-title"></h2>
        <p class="sub" id="dl-meta"></p>
        <h3>Data as it was</h3>
        <dl id="dl-fields"></dl>
        <div id="dl-children-wrap" hidden><h3>Deleted with it</h3><ul id="dl-children"></ul></div>
    </div>
</div>
@php
    $dlData = $logs->mapWithKeys(function ($l) {
        return [$l->id => [
            'title' => $l->entity_name ?: $l->typeLabel(),
            'meta' => $l->typeLabel().' · deleted by '.($l->admin_name ?: 'unknown').' on '.$l->created_at->format('d M Y, h:i A').($l->ip_address ? ' · '.$l->ip_address : ''),
            'record' => $l->snapshot['record'] ?? [],
            'children' => $l->snapshot['children'] ?? [],
        ]];
    });
@endphp
<script type="application/json" id="dl-data" nonce="{{ Vite::cspNonce() }}">@json($dlData)</script>
<style>
    .dl-chips{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 14px}
    .dl-chip{display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border:1px solid var(--line);border-radius:999px;background:var(--surface);color:var(--ink);font-size:12.5px;text-decoration:none}
    .dl-chip i{width:8px;height:8px;border-radius:50%;background:var(--c)}
    .dl-chip.on{border-color:var(--c,var(--primary));box-shadow:0 0 0 2px color-mix(in srgb,var(--c,var(--primary)) 20%,transparent)}
    .dl-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:14px}
    .dl-filters input{padding:9px 11px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink)}
    .dl-filters input[type=search]{flex:1 1 220px}
    .dl-filters label{display:flex;gap:6px;align-items:center;color:var(--muted);font-size:12px}
    .dl-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;color:var(--c);background:color-mix(in srgb,var(--c) 13%,transparent)}
    .dl-empty{display:grid;gap:4px;padding:40px;text-align:center;color:var(--muted)}
    .dl-pager{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:14px;font-size:12.5px;color:var(--muted)}.dl-pager a{padding:6px 12px;border:1px solid var(--line);border-radius:8px;color:var(--ink);text-decoration:none}.dl-pager .off{opacity:.4}
    .dl-card{position:relative;width:min(640px,100%);max-height:85vh;overflow:auto;padding:26px;border:1px solid var(--line);border-radius:16px;background:var(--surface);box-shadow:0 25px 80px #0008}
    .dl-card h2{margin:0 30px 4px 0;color:var(--ink)} .dl-card h3{margin:18px 0 8px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}
    .dl-x{position:absolute;top:12px;right:14px;border:0;background:transparent;font-size:26px;color:var(--muted);cursor:pointer}
    #dl-fields{display:grid;grid-template-columns:max-content 1fr;gap:6px 14px;margin:0;font-size:13px}
    #dl-fields dt{color:var(--muted)} #dl-fields dd{margin:0;color:var(--ink);word-break:break-word}
    #dl-children{margin:0;padding-left:18px;font-size:13px;color:var(--ink)}
</style>
<script nonce="{{ Vite::cspNonce() }}">
(function () {
    var data = JSON.parse(document.getElementById('dl-data').textContent), modal = document.getElementById('dl-modal');
    function close() { modal.style.display = 'none'; }
    function label(k) { return k.replace(/_/g, ' ').replace(/^./, function (c) { return c.toUpperCase(); }); }
    document.addEventListener('click', function (e) {
        var open = e.target.closest('[data-dl-open]');
        if (open) {
            var d = data[open.dataset.dlOpen]; if (!d) return;
            document.getElementById('dl-title').textContent = d.title;
            document.getElementById('dl-meta').textContent = d.meta;
            var dl = document.getElementById('dl-fields'); dl.innerHTML = '';
            Object.keys(d.record).forEach(function (k) {
                var v = d.record[k]; if (v === null || v === '') return;
                var dt = document.createElement('dt'), dd = document.createElement('dd');
                dt.textContent = label(k); dd.textContent = typeof v === 'object' ? JSON.stringify(v) : v;
                dl.append(dt, dd);
            });
            var ul = document.getElementById('dl-children'); ul.innerHTML = '';
            d.children.forEach(function (c) { var li = document.createElement('li'); li.textContent = c.name + (c.code ? ' (' + c.code + ')' : ''); ul.append(li); });
            document.getElementById('dl-children-wrap').hidden = !d.children.length;
            modal.style.display = 'grid';
        }
        if (e.target.closest('[data-dl-close]') || e.target === modal) close();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
@endsection
