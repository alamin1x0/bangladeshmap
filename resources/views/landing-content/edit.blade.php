@extends('layouts.app')

@php
    $num = fn ($v) => number_format((int) $v);
    $maxDay = max(1, collect($stats['days'])->max('visitors'));
    $deviceTotal = max(1, array_sum($stats['devices']->all()));
    $sourceMax = max(1, (int) ($stats['sources']->max() ?? 1));
    $sourceLabels = ['direct' => 'Direct / typed link', 'facebook' => 'Facebook', 'google' => 'Google', 'youtube' => 'YouTube', 'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'bing' => 'Bing'];
    $delta = function ($now, $before) {
        if ($before <= 0) { return null; }
        return (int) round(($now - $before) / $before * 100);
    };
    $todayDelta = $delta((int) $stats['today']->visitors, (int) $stats['yesterday']->visitors);
    $activeTab = array_key_exists((string) request('tab'), $sections) ? request('tab') : array_key_first($sections);
    $editedCount = count(array_intersect_key($overrides, \App\Support\LandingContent::defaults()));
@endphp

@push('head')
<style>
    .lc-flash { display:flex; align-items:center; gap:8px; margin-bottom:12px; padding:9px 13px; border-radius:11px; font-size:12.5px; font-weight:600; background:#e9f8ef; color:#146c3b; }
    .lc-flash.err { background:#fdecec; color:#b42318; }
    [data-theme="dark"] .lc-flash { background:#123222; color:#69d998; } [data-theme="dark"] .lc-flash.err { background:#3a1b1f; color:#ff9a9a; }
    .lc-flash a { color:inherit; margin-left:auto; }

    /* KPIs */
    .lc-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
    .lc-kpi { padding:12px 14px; border-radius:14px; background:var(--surface); box-shadow:var(--shadow); min-width:0; }
    .lc-kpi span { display:flex; align-items:center; gap:6px; color:var(--muted); font-size:11.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lc-kpi strong { display:block; font-size:21px; letter-spacing:-.5px; line-height:1.25; font-variant-numeric:tabular-nums; }
    .lc-kpi small { display:block; font-size:11px; color:var(--muted); }
    .lc-kpi .up { color:#0f8a4c; font-weight:700; } .lc-kpi .down { color:#c62828; font-weight:700; }
    [data-theme="dark"] .lc-kpi .up { color:#69d998; } [data-theme="dark"] .lc-kpi .down { color:#ff9a9a; }
    .lc-live { width:8px; height:8px; border-radius:50%; background:#0f9d58; box-shadow:0 0 0 0 #0f9d5866; animation:lcPulse 1.8s infinite; }
    @keyframes lcPulse { 70% { box-shadow:0 0 0 7px #0f9d5800; } 100% { box-shadow:0 0 0 0 #0f9d5800; } }
    @media (prefers-reduced-motion:reduce) { .lc-live { animation:none; } }

    /* Analytics row */
    .lc-ana { display:grid; grid-template-columns:minmax(0,1.7fr) minmax(0,1fr); gap:12px; margin-bottom:16px; }
    .lc-card { background:var(--surface); border-radius:16px; box-shadow:var(--shadow); padding:14px 16px; min-width:0; }
    .lc-card h2 { font-size:13px; margin:0 0 2px; }
    .lc-card .cap { font-size:11.5px; color:var(--muted); margin:0 0 12px; }
    .lc-chart { position:relative; display:grid; grid-template-columns:repeat(14,minmax(0,1fr)); gap:6px; align-items:end; height:150px; padding-bottom:20px; }
    .lc-col { position:relative; height:100%; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; cursor:default; outline:none; }
    .lc-col .bar { width:100%; max-width:26px; min-height:2px; border-radius:4px 4px 0 0; background:var(--primary); opacity:.85; transition:opacity .12s; }
    .lc-col.today .bar { opacity:1; }
    .lc-col.zero .bar { background:var(--line); opacity:1; }
    .lc-col .dot { position:absolute; top:-2px; width:6px; height:6px; border-radius:50%; background:#d08700; display:none; }
    .lc-col .lbl { position:absolute; bottom:-19px; font-size:10px; color:var(--muted); white-space:nowrap; }
    .lc-col .val { font-size:10px; font-weight:700; color:var(--ink); margin-bottom:3px; font-variant-numeric:tabular-nums; }
    .lc-chart:hover .lc-col .bar, .lc-chart:focus-within .lc-col .bar { opacity:.35; }
    .lc-col:hover .bar, .lc-col:focus .bar { opacity:1 !important; }
    .lc-tip { position:absolute; bottom:calc(100% + 6px); left:50%; transform:translateX(-50%); background:var(--ink); color:var(--surface); padding:7px 9px; border-radius:8px; font-size:11px; line-height:1.45; white-space:nowrap; pointer-events:none; opacity:0; transition:opacity .1s; z-index:3; }
    .lc-tip b { display:block; font-size:11.5px; }
    .lc-col:hover .lc-tip, .lc-col:focus .lc-tip { opacity:1; }
    .lc-col:first-child .lc-tip, .lc-col:nth-child(2) .lc-tip { left:0; transform:none; }
    .lc-col:last-child .lc-tip, .lc-col:nth-last-child(2) .lc-tip { left:auto; right:0; transform:none; }
    .lc-empty-chart { position:absolute; inset:0 0 20px; display:grid; place-items:center; font-size:12px; color:var(--muted); text-align:center; }

    .lc-src { display:grid; gap:7px; }
    .lc-src div { display:grid; grid-template-columns:110px minmax(0,1fr) 36px; gap:8px; align-items:center; font-size:12px; }
    .lc-src span { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lc-src i { display:block; height:8px; border-radius:0 4px 4px 0; background:var(--primary); min-width:2px; }
    .lc-src b { text-align:right; font-variant-numeric:tabular-nums; }
    .lc-dev { display:flex; gap:6px; margin-top:12px; padding-top:12px; border-top:1px dashed var(--line); }
    .lc-dev div { flex:1; text-align:center; padding:7px 4px; border-radius:10px; background:color-mix(in srgb, var(--primary) 6%, transparent); font-size:11px; color:var(--muted); }
    .lc-dev strong { display:block; font-size:15px; color:var(--ink); }

    /* Editor */
    .lc-editor { background:var(--surface); border-radius:16px; box-shadow:var(--shadow); }
    .lc-ehead { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:14px 16px 4px; flex-wrap:wrap; }
    .lc-ehead h2 { font-size:15px; margin:0; }
    .lc-ehead p { font-size:12px; color:var(--muted); margin:2px 0 0; }
    .lc-find { position:relative; flex:0 1 260px; }
    .lc-find svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--muted); }
    .lc-find input { width:100%; padding:8px 10px 8px 32px; border:0; border-radius:10px; background:color-mix(in srgb, var(--ink) 5%, transparent); color:var(--ink); font-size:12.5px; }
    .lc-find input:focus { outline:none; box-shadow:0 0 0 3px #2563eb33; }
    .lc-tabs { position:sticky; top:0; z-index:5; display:flex; gap:4px; overflow-x:auto; scrollbar-width:none; padding:10px 16px; background:var(--surface); border-radius:16px 16px 0 0; }
    .lc-tabs::-webkit-scrollbar { display:none; }
    .lc-tab { flex:0 0 auto; border:0; padding:7px 12px; border-radius:999px; background:transparent; color:var(--muted); font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; gap:6px; align-items:center; }
    .lc-tab:hover { color:var(--ink); background:color-mix(in srgb, var(--ink) 5%, transparent); }
    .lc-tab.on { background:var(--primary); color:#fff; }
    .lc-tab em { font-style:normal; font-size:10px; padding:0 6px; border-radius:99px; background:#d0870026; color:#a86d00; line-height:16px; }
    .lc-tab.on em { background:#ffffff33; color:#fff; }
    .lc-panel { padding:4px 16px 16px; display:grid; grid-template-columns:1fr 1fr; gap:12px 16px; }
    .lc-panel[hidden] { display:none; }
    .lc-field { min-width:0; }
    .lc-field.wide { grid-column:1 / -1; }
    .lc-field .top { display:flex; align-items:center; gap:6px; margin-bottom:4px; }
    .lc-field label { font-size:11.5px; font-weight:700; color:var(--muted); }
    .lc-field .badge { font-size:10px; font-weight:700; padding:1px 7px; border-radius:99px; background:#d0870022; color:#a86d00; display:none; }
    .lc-field.edited .badge { display:inline-block; }
    .lc-field.dirty .badge { display:inline-block; background:#2563eb1f; color:var(--primary); }
    [data-theme="dark"] .lc-field .badge { color:#ffc94f; }
    .lc-field .reset { margin-left:auto; border:0; background:none; color:var(--muted); font-size:11px; cursor:pointer; padding:0; display:none; }
    .lc-field .reset:hover { color:var(--primary); text-decoration:underline; }
    .lc-field.edited .reset, .lc-field.dirty .reset { display:inline; }
    .lc-field .input { width:100%; }
    .lc-field textarea.input { min-height:74px; resize:vertical; line-height:1.55; }
    .lc-field .hint { font-size:10.5px; color:var(--muted); margin-top:3px; }
    .lc-field.hide { display:none; }
    .lc-nores { grid-column:1 / -1; padding:24px; text-align:center; color:var(--muted); font-size:12.5px; display:none; }

    .lc-tokens { display:flex; flex-wrap:wrap; gap:6px; align-items:center; padding:0 16px 6px; font-size:11.5px; color:var(--muted); }
    .lc-tk { border:0; background:none; padding:0; cursor:pointer; }
    .lc-tokens code { padding:2px 7px; border-radius:6px; background:color-mix(in srgb, var(--primary) 8%, transparent); color:var(--primary); font-size:11px; cursor:pointer; border:0; font-family:ui-monospace,monospace; }

    .lc-save { position:sticky; bottom:0; z-index:6; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:11px 16px; border-radius:0 0 16px 16px; background:color-mix(in srgb, var(--surface) 92%, transparent); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); box-shadow:0 -8px 20px -16px #0f172a55; }
    .lc-save .state { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:7px; }
    .lc-save .state i { width:8px; height:8px; border-radius:50%; background:#0f9d58; }
    .lc-save.dirty .state i { background:#d08700; }
    .lc-save .acts { display:flex; gap:8px; }

    @media (max-width:1200px) { .lc-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:980px) { .lc-ana { grid-template-columns:1fr; } }
    @media (max-width:650px) {
        .lc-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .lc-kpi { padding:10px 12px; }
        .lc-panel { grid-template-columns:1fr; padding:4px 12px 14px; }
        .lc-chart { gap:3px; }
        .lc-col .val { display:none; }
        .lc-col:nth-child(odd) .lbl { visibility:hidden; }
        .lc-find { flex:1 1 100%; }
        .lc-tabs { padding:10px 12px; }
        .lc-save .state span { display:none; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <p class="eyebrow">Website</p>
            <h1>Landing page</h1>
            <p class="sub">See who visits, and edit every text on the website. Saved changes go live instantly.</p>
        </div>
    </div>
    <div class="top-actions">
        <a class="btn" href="{{ route('home') }}" target="_blank" rel="noopener">Open website ↗</a>
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
    </div>
</header>

@if(session('success'))<div class="lc-flash" role="status">✓ {{ session('success') }} <a href="{{ route('home') }}" target="_blank" rel="noopener">See it live ↗</a></div>@endif
@if($errors->any())<div class="lc-flash err" role="alert">{{ $errors->first() }}</div>@endif

{{-- Visitors --}}
<section class="lc-kpis" aria-label="Visitor summary">
    <div class="lc-kpi"><span><i class="lc-live" aria-hidden="true"></i>On the site now</span><strong>{{ $num($stats['online']) }}</strong><small>last 5 minutes</small></div>
    <div class="lc-kpi"><span>Visitors today</span><strong>{{ $num($stats['today']->visitors) }}</strong>
        <small>{{ $num($stats['today']->views) }} views @if($todayDelta !== null)· <b class="{{ $todayDelta >= 0 ? 'up' : 'down' }}">{{ $todayDelta >= 0 ? '▲' : '▼' }} {{ abs($todayDelta) }}%</b> vs yesterday @endif</small></div>
    <div class="lc-kpi"><span>Yesterday</span><strong>{{ $num($stats['yesterday']->visitors) }}</strong><small>{{ $num($stats['yesterday']->views) }} views</small></div>
    <div class="lc-kpi"><span>Last 7 days</span><strong>{{ $num($stats['week']->visitors) }}</strong><small>{{ $num($stats['week']->views) }} views</small></div>
    <div class="lc-kpi"><span>Last 30 days</span><strong>{{ $num($stats['month']->visitors) }}</strong><small>{{ $num($stats['total']) }} views all time</small></div>
    <div class="lc-kpi"><span>Visitors → orders</span><strong>{{ $stats['conversion'] !== null ? $stats['conversion'].'%' : '—' }}</strong><small>{{ $num($stats['monthOrders']) }} orders in 30 days</small></div>
</section>

<section class="lc-ana">
    <div class="lc-card">
        <h2>Visitors per day</h2>
        <p class="cap">Unique visitors, last 14 days (Bangladesh time). Hover a bar for views and orders.</p>
        <div class="lc-chart" role="img" aria-label="Unique visitors per day for the last 14 days">
            @foreach($stats['days'] as $day)
                @php $pct = $day['visitors'] / $maxDay * 100; @endphp
                <div class="lc-col {{ $loop->last ? 'today' : '' }} {{ $day['visitors'] === 0 ? 'zero' : '' }}" tabindex="0" aria-label="{{ $day['date']->format('D d M') }}: {{ $day['visitors'] }} visitors, {{ $day['views'] }} views, {{ $day['orders'] }} orders">
                    <span class="lc-tip"><b>{{ $loop->last ? 'Today' : $day['date']->format('D, d M') }}</b>{{ $num($day['visitors']) }} visitors · {{ $num($day['views']) }} views<br>{{ $num($day['orders']) }} {{ $day['orders'] === 1 ? 'order' : 'orders' }}</span>
                    @if($day['visitors'] > 0)<span class="val">{{ $day['visitors'] }}</span>@endif
                    <span class="bar" style="height:{{ max(1.5, round($pct * .82, 1)) }}%"></span>
                    <span class="lbl">{{ $loop->last ? 'Today' : $day['date']->format('d M') }}</span>
                </div>
            @endforeach
            @if($maxDay === 1 && collect($stats['days'])->sum('visitors') === 0)<div class="lc-empty-chart">No visitors yet — share your website link to start seeing data.</div>@endif
        </div>
    </div>
    <div class="lc-card">
        <h2>Where visitors come from</h2>
        <p class="cap">Last 30 days · add <code>?ref=facebook</code> to a link to track a campaign</p>
        @if($stats['sources']->isEmpty())
            <p class="cap" style="margin:18px 0">No data yet.</p>
        @else
            <div class="lc-src">
                @foreach($stats['sources'] as $src => $c)
                    <div><span title="{{ $src }}">{{ $sourceLabels[$src] ?? $src }}</span><i style="width:{{ round($c / $sourceMax * 100) }}%"></i><b>{{ $num($c) }}</b></div>
                @endforeach
            </div>
        @endif
        <div class="lc-dev">
            @foreach(['mobile' => '📱 Mobile', 'desktop' => '💻 Desktop', 'tablet' => 'Tablet'] as $key => $label)
                <div><strong>{{ round(($stats['devices'][$key] ?? 0) / $deviceTotal * 100) }}%</strong>{{ $label }}</div>
            @endforeach
        </div>
    </div>
</section>

{{-- Text editor --}}
<form class="lc-editor" method="post" action="{{ route('landing-content.update') }}" id="lc-form" data-submit-once data-loading-text="Publishing…">
    @csrf @method('PUT')
    <input type="hidden" name="tab" id="lc-tab-input" value="{{ $activeTab }}">
    <div class="lc-ehead">
        <div>
            <h2>Website text</h2>
            <p>{{ $editedCount }} of {{ count(\App\Support\LandingContent::defaults()) }} texts customised. Leave a box empty to use the original text.</p>
        </div>
        <label class="lc-find"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" id="lc-find" placeholder="Find a text… (e.g. মূল্য, FAQ)" aria-label="Find a text"></label>
    </div>
    <div class="lc-tokens">Auto values (click to copy): @foreach(\App\Support\LandingContent::TOKENS as $tk)<button type="button" class="lc-tk" data-token="{{ $tk }}"><code>{{ $tk }}</code></button>@endforeach</div>

    <nav class="lc-tabs" role="tablist" aria-label="Page sections">
        @foreach($sections as $id => [$label, $fields])
            @php $n = count(array_intersect_key($overrides, $fields)); @endphp
            <button type="button" class="lc-tab {{ $id === $activeTab ? 'on' : '' }}" role="tab" data-tab="{{ $id }}" aria-selected="{{ $id === $activeTab ? 'true' : 'false' }}" aria-controls="panel-{{ $id }}">{{ $label }}@if($n)<em>{{ $n }}</em>@endif</button>
        @endforeach
    </nav>

    @foreach($sections as $id => [$label, $fields])
        <div class="lc-panel" id="panel-{{ $id }}" role="tabpanel" data-panel="{{ $id }}" @if($id !== $activeTab) hidden @endif>
            @foreach($fields as $key => [$fieldLabel, $default, $type])
                @php
                    $value = old('content.'.$key, $overrides[$key] ?? $default);
                    $isEdited = array_key_exists($key, $overrides);
                    $fid = 'f-'.str_replace('.', '-', $key);
                    $wide = $type === 'text' || str_contains($fieldLabel, '|');
                @endphp
                <div class="lc-field {{ $isEdited ? 'edited' : '' }} {{ $wide ? 'wide' : '' }}" data-search="{{ mb_strtolower($fieldLabel.' '.$value.' '.$label) }}">
                    <div class="top">
                        <label for="{{ $fid }}">{{ $fieldLabel }}</label><span class="badge">{{ $isEdited ? 'Edited' : 'Changed' }}</span>
                        <button type="button" class="reset" data-default="{{ $default }}" title="Go back to the original text">Reset to original</button>
                    </div>
                    @if($type === 'text')
                        <textarea class="input" id="{{ $fid }}" name="content[{{ $key }}]" maxlength="3000" rows="{{ min(6, max(2, substr_count($value, "\n") + 2)) }}" data-initial="{{ $value }}">{{ $value }}</textarea>
                    @else
                        <input class="input" id="{{ $fid }}" type="text" name="content[{{ $key }}]" value="{{ $value }}" maxlength="3000" data-initial="{{ $value }}">
                    @endif
                    @if(str_contains($fieldLabel, '|'))<p class="hint">Separate parts with a vertical bar <b>|</b></p>@elseif($type === 'text' && str_contains($default, "\n"))<p class="hint">Each new line is shown as a new line on the website.</p>@endif
                </div>
            @endforeach
        </div>
    @endforeach
    <div class="lc-panel"><div class="lc-nores" id="lc-nores">No text matches your search.</div></div>

    <div class="lc-save" id="lc-save">
        <span class="state"><i></i><b id="lc-state">All changes are live</b><span>&nbsp;· the website updates the moment you publish</span></span>
        <div class="acts">
            <button type="button" class="btn" id="lc-discard" hidden>Discard</button>
            <button type="submit" class="btn btn-primary"><span data-label>Publish changes</span></button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const form = document.getElementById('lc-form');
    const tabs = [...document.querySelectorAll('.lc-tab')];
    const panels = [...document.querySelectorAll('[data-panel]')];
    const tabInput = document.getElementById('lc-tab-input');
    const fields = [...form.querySelectorAll('[name^="content["]')];
    const bar = document.getElementById('lc-save'), state = document.getElementById('lc-state'), discard = document.getElementById('lc-discard');
    const find = document.getElementById('lc-find'), nores = document.getElementById('lc-nores');

    const show = id => {
        tabs.forEach(t => { const on = t.dataset.tab === id; t.classList.toggle('on', on); t.setAttribute('aria-selected', on); });
        panels.forEach(p => p.hidden = p.dataset.panel !== id);
        tabInput.value = id;
        try { history.replaceState(null, '', '?tab=' + id); } catch (e) {}
    };
    tabs.forEach(t => t.addEventListener('click', () => { find.value = ''; filter(); show(t.dataset.tab); }));

    // Unsaved-change tracking
    const refresh = () => {
        let dirty = 0;
        fields.forEach(f => {
            const changed = f.value !== f.dataset.initial;
            f.closest('.lc-field').classList.toggle('dirty', changed);
            if (changed) dirty++;
        });
        bar.classList.toggle('dirty', dirty > 0);
        discard.hidden = dirty === 0;
        state.textContent = dirty ? dirty + (dirty === 1 ? ' unsaved change' : ' unsaved changes') : 'All changes are live';
        return dirty;
    };
    fields.forEach(f => f.addEventListener('input', refresh));
    discard.addEventListener('click', () => { fields.forEach(f => f.value = f.dataset.initial); refresh(); });
    let submitting = false;
    form.addEventListener('submit', () => { submitting = true; });
    window.addEventListener('beforeunload', e => { if (!submitting && refresh()) { e.preventDefault(); e.returnValue = ''; } });

    // Reset to original
    form.querySelectorAll('.reset').forEach(b => b.addEventListener('click', () => {
        const f = b.closest('.lc-field').querySelector('[name^="content["]');
        f.value = b.dataset.default; f.dispatchEvent(new Event('input')); f.focus();
    }));

    // Copy tokens
    document.querySelectorAll('.lc-tk').forEach(b => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.token); const c = b.firstElementChild, o = c.textContent; c.textContent = 'copied'; setTimeout(() => c.textContent = o, 900); } catch (e) {}
    }));

    // Search across all sections
    const filter = () => {
        const q = find.value.trim().toLowerCase();
        let hits = 0;
        form.querySelectorAll('.lc-field').forEach(el => {
            const field = el.querySelector('[name^="content["]');
            const match = !q || el.dataset.search.includes(q) || field.value.toLowerCase().includes(q);
            el.classList.toggle('hide', !match);
            if (match) hits++;
        });
        if (q) { panels.forEach(p => p.hidden = false); tabs.forEach(t => t.classList.remove('on')); }
        else { show(tabInput.value); }
        nores.style.display = q && !hits ? 'block' : 'none';
    };
    find.addEventListener('input', filter);
    refresh();
})();
</script>
@endpush
