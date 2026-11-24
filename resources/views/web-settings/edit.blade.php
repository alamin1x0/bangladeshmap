@extends('layouts.app')

@php
    $tabs = [
        'brand' => ['Branding', '<path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/>'],
        'status' => ['Website status', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        'contact' => ['Support & contact', '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>'],
        'announce' => ['Announcement', '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>'],
        'app' => ['Mobile app', '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>'],
        'sections' => ['Page sections', '<rect x="3" y="3" width="18" height="7" rx="1.5"/><rect x="3" y="14" width="18" height="7" rx="1.5"/>'],
        'advanced' => ['Advanced', '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>'],
    ];
    $active = array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'brand';
    if ($errors->hasAny(['site_status', 'maintenance_title', 'maintenance_message', 'maintenance_until', 'down_message'])) { $active = 'status'; }
    elseif ($errors->hasAny(['support_phone', 'support_email', 'whatsapp', 'facebook_url', 'youtube_url', 'address', 'office_hours'])) { $active = 'contact'; }
    elseif ($errors->hasAny(['announcement_text', 'announcement_link'])) { $active = 'announce'; }
    elseif ($errors->hasAny(['play_url', 'ios_url', 'app_name', 'app_title', 'app_text', 'app_rating', 'app_downloads'])) { $active = 'app'; }
    elseif ($errors->hasAny(['site_name', 'tagline', 'theme_color', 'logo', 'favicon'])) { $active = 'brand'; }
    $v = fn ($k) => old($k, $s[$k]);
    $on = fn ($k) => (bool) old($k, $s[$k]);
    $statusMeta = [
        'live' => ['Live', 'Everyone can see the website and order.', '#0f9d58'],
        'maintenance' => ['Maintenance', 'Visitors see a “we are updating” page with your contact.', '#d08700'],
        'down' => ['Offline', 'Website is closed. Visitors see a short “closed” message.', '#d93a3a'],
    ];
    $toggle = function (string $key, string $title, string $desc) use ($on) {
        return '<label class="ws-toggle"><span><b>'.e($title).'</b><small>'.e($desc).'</small></span><input type="hidden" name="'.$key.'" value="0"><input type="checkbox" name="'.$key.'" value="1" '.($on($key) ? 'checked' : '').'><span class="track" aria-hidden="true"></span></label>';
    };
    $err = fn ($k) => $errors->has($k) ? '<p class="ws-err">'.e($errors->first($k)).'</p>' : '';
@endphp

@push('head')
<style>
    .ws-flash { display:flex; align-items:center; gap:8px; margin-bottom:12px; padding:9px 13px; border-radius:11px; font-size:12.5px; font-weight:600; background:#e9f8ef; color:#146c3b; }
    .ws-flash.err { background:#fdecec; color:#b42318; }
    [data-theme="dark"] .ws-flash { background:#123222; color:#69d998; } [data-theme="dark"] .ws-flash.err { background:#3a1b1f; color:#ff9a9a; }
    .ws-flash a { margin-left:auto; color:inherit; }

    .ws { display:grid; grid-template-columns:210px minmax(0,1fr); gap:14px; align-items:start; }
    .ws-nav { position:sticky; top:12px; display:grid; gap:3px; padding:8px; border-radius:16px; background:var(--surface); box-shadow:var(--shadow); }
    .ws-nav button { display:flex; align-items:center; gap:9px; width:100%; border:0; background:transparent; color:var(--muted); padding:9px 10px; border-radius:10px; font-size:12.5px; font-weight:700; cursor:pointer; text-align:left; }
    .ws-nav button svg { width:16px; height:16px; flex:0 0 16px; }
    .ws-nav button:hover { background:color-mix(in srgb, var(--ink) 5%, transparent); color:var(--ink); }
    .ws-nav button.on { background:var(--primary); color:#fff; }
    .ws-nav .dot { margin-left:auto; width:8px; height:8px; border-radius:50%; }

    .ws-main { min-width:0; background:var(--surface); border-radius:16px; box-shadow:var(--shadow); }
    .ws-panel { padding:18px 20px 8px; }
    .ws-panel[hidden] { display:none; }
    .ws-panel > h2 { font-size:15px; margin:0; }
    .ws-panel > .lead { font-size:12px; color:var(--muted); margin:2px 0 16px; }
    .ws-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 16px; margin-bottom:14px; }
    .ws-grid .full { grid-column:1 / -1; }
    .ws-f label.l { display:block; font-size:11.5px; font-weight:700; color:var(--muted); margin-bottom:5px; }
    .ws-f .input { width:100%; }
    .ws-f textarea.input { min-height:76px; resize:vertical; }
    .ws-f .hint { font-size:11px; color:var(--muted); margin-top:4px; }
    .ws-err { font-size:11.5px; color:#c62828; margin-top:4px; font-weight:600; }

    /* Image uploaders */
    .ws-imgs { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
    .ws-img { border-radius:14px; padding:14px; background:color-mix(in srgb, var(--primary) 4%, transparent); }
    .ws-img h3 { font-size:12.5px; margin:0 0 2px; }
    .ws-img p.d { font-size:11px; color:var(--muted); margin:0 0 12px; }
    .ws-prev { display:grid; place-items:center; height:110px; border-radius:12px; background:var(--surface) repeating-conic-gradient(#94a3b814 0 25%, transparent 0 50%) 0 0 / 16px 16px; margin-bottom:10px; position:relative; overflow:hidden; }
    .ws-prev img { max-width:80%; max-height:76px; object-fit:contain; }
    .ws-prev .empty { font-size:12px; color:var(--muted); font-weight:600; }
    .ws-fav-row { display:flex; align-items:end; justify-content:center; gap:16px; }
    .ws-fav-row figure { margin:0; text-align:center; font-size:10px; color:var(--muted); }
    .ws-fav-row img { display:block; margin:0 auto 4px; border-radius:22%; }
    .ws-tab-mock { display:flex; align-items:center; gap:7px; padding:6px 12px; border-radius:9px 9px 0 0; background:var(--surface); box-shadow:0 1px 0 var(--line); font-size:11px; font-weight:600; max-width:170px; }
    .ws-tab-mock img { width:16px; height:16px; border-radius:3px; }
    .ws-img .acts { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .ws-img .acts label.btn { cursor:pointer; }
    .ws-img .acts input[type=file] { position:absolute; width:1px; height:1px; opacity:0; }
    .ws-img .rm { border:0; background:none; color:#c62828; font-size:12px; font-weight:700; cursor:pointer; }
    .ws-img .rm[hidden] { display:none; }
    .ws-img .fname { font-size:11px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; }
    .ws-color { display:flex; align-items:center; gap:8px; }
    .ws-color input[type=color] { width:42px; height:38px; padding:2px; border:1px solid var(--line); border-radius:9px; background:var(--surface); cursor:pointer; }
    .ws-swatches { display:flex; gap:6px; flex-wrap:wrap; margin-top:7px; }
    .ws-swatches button { width:22px; height:22px; border-radius:50%; border:2px solid var(--surface); box-shadow:0 0 0 1px var(--line); cursor:pointer; }

    /* Status cards */
    .ws-status { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .ws-st { position:relative; display:block; padding:14px; border-radius:14px; background:color-mix(in srgb, var(--c) 6%, transparent); cursor:pointer; transition:box-shadow .15s, background .15s; }
    .ws-st input { position:absolute; opacity:0; }
    .ws-st b { display:flex; align-items:center; gap:7px; font-size:13.5px; }
    .ws-st b i { width:9px; height:9px; border-radius:50%; background:var(--c); }
    .ws-st small { display:block; font-size:11.5px; color:var(--muted); margin-top:4px; line-height:1.45; }
    .ws-st:has(input:checked) { background:color-mix(in srgb, var(--c) 14%, transparent); box-shadow:inset 0 0 0 2px var(--c); }
    .ws-st:has(input:focus-visible) { outline:3px solid #2563eb55; }
    .ws-when[hidden] { display:none; }
    .ws-note { display:flex; gap:9px; padding:10px 12px; border-radius:12px; background:color-mix(in srgb, #2563eb 6%, transparent); font-size:12px; color:var(--muted); margin-bottom:14px; }

    /* Toggles */
    .ws-toggles { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px; }
    .ws-toggle { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 13px; border-radius:12px; background:color-mix(in srgb, var(--ink) 3.5%, transparent); cursor:pointer; transition:background .15s; }
    .ws-toggle:hover { background:color-mix(in srgb, var(--ink) 6%, transparent); }
    .ws-toggle b { display:block; font-size:12.5px; }
    .ws-toggle small { display:block; font-size:11px; color:var(--muted); line-height:1.4; margin-top:1px; }
    .ws-toggle input[type=checkbox] { position:absolute; opacity:0; pointer-events:none; }
    .ws-toggle .track { position:relative; width:36px; height:20px; flex:0 0 36px; border-radius:99px; background:#cbd5e1; transition:background .15s; }
    [data-theme="dark"] .ws-toggle .track { background:#475569; }
    .ws-toggle .track::after { content:""; position:absolute; left:2px; top:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px #0003; transition:left .15s; }
    .ws-toggle input:checked + .track { background:#0f9d58; }
    .ws-toggle input:checked + .track::after { left:18px; }
    .ws-toggle input:focus-visible + .track { box-shadow:0 0 0 3px #2563eb55; }

    .ws-announce-prev { border-radius:10px; overflow:hidden; margin-bottom:14px; font-size:13px; }
    .ws-announce-prev div { background:#f4c34d; color:#123526; text-align:center; font-weight:700; padding:8px 12px; }
    .ws-announce-prev span { display:block; background:#1f4d3a; height:24px; }

    .ws-save { position:sticky; bottom:0; z-index:4; display:flex; justify-content:space-between; align-items:center; gap:10px; padding:11px 20px; border-top:1px solid var(--line); border-radius:0 0 16px 16px; background:color-mix(in srgb, var(--surface) 94%, transparent); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); }
    .ws-save .state { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:7px; }
    .ws-save .state i { width:8px; height:8px; border-radius:50%; background:#0f9d58; }
    .ws-save.dirty .state i { background:#d08700; }

    /* Mobile app tab */
    .ws-stores { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
    .ws-store { padding:14px; border-radius:14px; background:color-mix(in srgb, var(--ink) 3.5%, transparent); }
    .ws-store .head { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .ws-store .head b { display:block; font-size:13px; } .ws-store .head small { display:block; font-size:11px; color:var(--muted); }
    .ws-store label.l { display:block; font-size:11.5px; font-weight:700; color:var(--muted); margin-bottom:5px; }
    .badge-mini { display:grid; place-items:center; width:36px; height:36px; border-radius:10px; background:var(--surface); box-shadow:var(--shadow); }
    .badge-mini svg { width:20px; height:20px; }
    .badge-mini.ios { color:var(--ink); }
    .ws-url { display:flex; gap:6px; } .ws-url .input { flex:1; min-width:0; } .ws-url .btn[hidden] { display:none; }
    .ws-mini-toggle { margin-left:auto; cursor:pointer; }
    .ws-mini-toggle input[type=checkbox] { position:absolute; opacity:0; pointer-events:none; }
    .ws-mini-toggle .track { display:block; position:relative; width:36px; height:20px; border-radius:99px; background:#cbd5e1; transition:background .15s; }
    .ws-mini-toggle .track::after { content:""; position:absolute; left:2px; top:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px #0003; transition:left .15s; }
    .ws-mini-toggle input:checked + .track { background:#0f9d58; } .ws-mini-toggle input:checked + .track::after { left:18px; }
    .ws-mini-toggle input:focus-visible + .track { box-shadow:0 0 0 3px #2563eb55; }
    .ws-app-prev { border-radius:16px; padding:20px; background:linear-gradient(150deg,#1f4d3a,#123526); color:#fff; margin-bottom:16px; }
    .ws-app-prev .name { display:flex; align-items:center; gap:7px; font-size:12.5px; flex-wrap:wrap; }
    .ws-app-prev .name img { border-radius:7px; }
    .ws-app-prev .name em { font-style:normal; font-size:11px; padding:1px 8px; border-radius:99px; background:#ffffff1f; }
    .ws-app-prev .name em[hidden] { display:none; }
    .ws-app-prev h4 { font-size:17px; margin:10px 0 4px; line-height:1.35; }
    .ws-app-prev p { font-size:12.5px; color:#cfe2d7; margin:0 0 14px; }
    .ws-app-prev .badges { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .ws-app-prev .sb { display:inline-flex; align-items:center; gap:8px; padding:7px 13px 7px 10px; border-radius:10px; background:#000; border:1px solid #ffffff40; font-weight:700; font-size:14px; line-height:1.05; }
    .ws-app-prev .sb svg { width:20px; height:20px; }
    .ws-app-prev .sb small { display:block; font-size:9px; font-weight:500; opacity:.85; }
    .ws-app-prev .sb[hidden], .ws-app-prev .none[hidden] { display:none; }
    .ws-app-prev .none { font-size:12px; color:#cfe2d7; }
    @media (max-width:650px) { .ws-stores { grid-template-columns:1fr; } }

    @media (max-width:980px) {
        .ws { grid-template-columns:1fr; }
        .ws-nav { position:static; display:flex; overflow-x:auto; scrollbar-width:none; }
        .ws-nav::-webkit-scrollbar { display:none; }
        .ws-nav button { width:auto; flex:0 0 auto; }
    }
    @media (max-width:650px) {
        .ws-panel { padding:16px 14px 6px; }
        .ws-grid, .ws-imgs, .ws-toggles { grid-template-columns:1fr; }
        .ws-status { grid-template-columns:1fr; }
        .ws-save { padding:10px 14px; }
    }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <p class="eyebrow">Website</p>
            <h1>Web settings</h1>
            <p class="sub">Logo, favicon, website on/off, support contact and advanced options — all in one place.</p>
        </div>
    </div>
    <div class="top-actions">
        <a class="btn" href="{{ route('home') }}" target="_blank" rel="noopener">Open website ↗</a>
        <button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button>
    </div>
</header>

@if(session('success'))<div class="ws-flash" role="status">✓ {{ session('success') }} <a href="{{ route('home') }}" target="_blank" rel="noopener">View ↗</a></div>@endif
@if($errors->any())<div class="ws-flash err" role="alert">{{ $errors->first() }}</div>@endif

<form method="post" action="{{ route('web-settings.update') }}" enctype="multipart/form-data" id="ws-form" class="ws" data-submit-once data-loading-text="Saving…">
    @csrf @method('PUT')
    <input type="hidden" name="tab" id="ws-tab" value="{{ $active }}">

    <nav class="ws-nav" aria-label="Settings sections">
        @foreach($tabs as $id => [$label, $icon])
            <button type="button" data-tab="{{ $id }}" class="{{ $id === $active ? 'on' : '' }}" aria-controls="ws-{{ $id }}" aria-selected="{{ $id === $active ? 'true' : 'false' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>{{ $label }}
                @if($id === 'status')<i class="dot" style="background:{{ $statusMeta[$s['site_status']][2] }}" title="{{ $statusMeta[$s['site_status']][0] }}"></i>@endif
            </button>
        @endforeach
    </nav>

    <div class="ws-main">
        {{-- Branding --}}
        <section class="ws-panel" id="ws-brand" data-panel="brand" @if($active !== 'brand') hidden @endif>
            <h2>Branding</h2>
            <p class="lead">Your logo and browser icon appear on the website, login page and admin panel.</p>
            <div class="ws-imgs">
                <div class="ws-img">
                    <h3>Logo</h3>
                    <p class="d">PNG (transparent) or JPG, wide shape works best · max 4 MB</p>
                    <div class="ws-prev" id="logo-prev">
                        @if($logoUrl)<img src="{{ $logoUrl }}" alt="Current logo">@else<span class="empty">No logo — site name is shown as text</span>@endif
                    </div>
                    <div class="acts">
                        <label class="btn" for="logo-in">⬆ {{ $logoUrl ? 'Replace logo' : 'Upload logo' }}</label>
                        <input type="file" id="logo-in" name="logo" accept="image/png,image/jpeg" data-preview="logo-prev">
                        <input type="hidden" name="remove_logo" value="0" id="remove_logo">
                        <button type="button" class="rm" data-remove="logo" @if(! $logoUrl) hidden @endif>Remove</button>
                        <span class="fname" id="logo-in-name"></span>
                    </div>
                    {!! $err('logo') !!}
                </div>
                <div class="ws-img">
                    <h3>Favicon (browser icon)</h3>
                    <p class="d">Square PNG/JPG, at least 512×512 is ideal · we make all sizes automatically</p>
                    <div class="ws-prev" id="favicon-prev">
                        <div class="ws-fav-row">
                            <figure><img src="{{ $icons['192'] }}" width="56" height="56" alt="" data-fav>App</figure>
                            <figure><img src="{{ $icons['32'] }}" width="32" height="32" alt="" data-fav>32px</figure>
                            <figure><div class="ws-tab-mock"><img src="{{ $icons['32'] }}" alt="" data-fav>{{ $s['site_name'] }}</div>Browser tab</figure>
                        </div>
                    </div>
                    <div class="acts">
                        <label class="btn" for="fav-in">⬆ Upload icon</label>
                        <input type="file" id="fav-in" name="favicon" accept="image/png,image/jpeg" data-preview="favicon-prev">
                        <input type="hidden" name="remove_favicon" value="0" id="remove_favicon">
                        <button type="button" class="rm" data-remove="favicon" @if(! $s['favicon']) hidden @endif>Use default icon</button>
                        <span class="fname" id="fav-in-name"></span>
                    </div>
                    {!! $err('favicon') !!}
                </div>
            </div>
            <div class="ws-grid">
                <div class="ws-f"><label class="l" for="site_name">Site name</label><input class="input" id="site_name" name="site_name" value="{{ $v('site_name') }}" maxlength="60" required>{!! $err('site_name') !!}</div>
                <div class="ws-f"><label class="l" for="tagline">Tagline</label><input class="input" id="tagline" name="tagline" value="{{ $v('tagline') }}" maxlength="120">{!! $err('tagline') !!}</div>
                <div class="ws-f">
                    <label class="l" for="theme_color">Brand colour <span style="font-weight:500">(phone browser bar, status page)</span></label>
                    <div class="ws-color"><input type="color" id="theme_picker" value="{{ $v('theme_color') }}" aria-label="Pick colour"><input class="input" id="theme_color" name="theme_color" value="{{ $v('theme_color') }}" maxlength="7" pattern="#[0-9a-fA-F]{6}"></div>
                    <div class="ws-swatches">@foreach(['#1f4d3a', '#2563eb', '#0f766e', '#7c3aed', '#be123c', '#c2410c', '#0f172a'] as $c)<button type="button" data-color="{{ $c }}" style="background:{{ $c }}" aria-label="Use {{ $c }}"></button>@endforeach</div>
                    {!! $err('theme_color') !!}
                </div>
            </div>
        </section>

        {{-- Status --}}
        <section class="ws-panel" id="ws-status" data-panel="status" @if($active !== 'status') hidden @endif>
            <h2>Website status</h2>
            <p class="lead">Turn the public website on or off. You stay logged in and can still preview it.</p>
            <div class="ws-status" role="radiogroup" aria-label="Website status">
                @foreach($statusMeta as $key => [$label, $desc, $color])
                    <label class="ws-st" style="--c:{{ $color }}"><input type="radio" name="site_status" value="{{ $key }}" @checked($v('site_status') === $key)><b><i></i>{{ $label }}</b><small>{{ $desc }}</small></label>
                @endforeach
            </div>
            <div class="ws-note">ℹ️ This only affects the public website (landing page & order form). The admin panel and the mobile app keep working — the app has its own switch in App Settings.</div>
            <div class="ws-grid ws-when" data-when="maintenance">
                <div class="ws-f full"><label class="l" for="maintenance_title">Maintenance title</label><input class="input" id="maintenance_title" name="maintenance_title" value="{{ $v('maintenance_title') }}" maxlength="150">{!! $err('maintenance_title') !!}</div>
                <div class="ws-f full"><label class="l" for="maintenance_message">Maintenance message</label><textarea class="input" id="maintenance_message" name="maintenance_message" maxlength="1000">{{ $v('maintenance_message') }}</textarea>{!! $err('maintenance_message') !!}</div>
                <div class="ws-f"><label class="l" for="maintenance_until">Back online at (optional)</label><input class="input" type="datetime-local" id="maintenance_until" name="maintenance_until" value="{{ $v('maintenance_until') }}"><p class="hint">Bangladesh time. Visitors see a live countdown.</p>{!! $err('maintenance_until') !!}</div>
            </div>
            <div class="ws-grid ws-when" data-when="down">
                <div class="ws-f full"><label class="l" for="down_message">Offline message</label><textarea class="input" id="down_message" name="down_message" maxlength="1000">{{ $v('down_message') }}</textarea>{!! $err('down_message') !!}</div>
            </div>
        </section>

        {{-- Contact --}}
        <section class="ws-panel" id="ws-contact" data-panel="contact" @if($active !== 'contact') hidden @endif>
            <h2>Support & contact</h2>
            <p class="lead">Shown on the website footer, order section and maintenance page.</p>
            <div class="ws-grid">
                <div class="ws-f"><label class="l" for="support_phone">Support phone</label><input class="input" id="support_phone" name="support_phone" value="{{ $v('support_phone') }}" inputmode="tel" maxlength="30" placeholder="{{ $fallbackPhone ?: '01XXXXXXXXX' }}">@if($s['support_phone'] === '' && $fallbackPhone)<p class="hint">Empty — currently using {{ $fallbackPhone }} from Book Orders / App Settings.</p>@endif{!! $err('support_phone') !!}</div>
                <div class="ws-f"><label class="l" for="support_email">Support email</label><input class="input" type="email" id="support_email" name="support_email" value="{{ $v('support_email') }}" maxlength="190" placeholder="support@example.com">{!! $err('support_email') !!}</div>
                <div class="ws-f"><label class="l" for="whatsapp">WhatsApp number</label><input class="input" id="whatsapp" name="whatsapp" value="{{ $v('whatsapp') }}" inputmode="tel" maxlength="20" placeholder="8801712345678"><p class="hint">With country code, digits only.</p>{!! $err('whatsapp') !!}</div>
                <div class="ws-f"><label class="l" for="office_hours">Office hours</label><input class="input" id="office_hours" name="office_hours" value="{{ $v('office_hours') }}" maxlength="120" placeholder="শনি–বৃহস্পতি, সকাল ১০টা–সন্ধ্যা ৬টা">{!! $err('office_hours') !!}</div>
                <div class="ws-f"><label class="l" for="facebook_url">Facebook page</label><input class="input" type="url" id="facebook_url" name="facebook_url" value="{{ $v('facebook_url') }}" maxlength="300" placeholder="https://facebook.com/yourpage">{!! $err('facebook_url') !!}</div>
                <div class="ws-f"><label class="l" for="youtube_url">YouTube channel</label><input class="input" type="url" id="youtube_url" name="youtube_url" value="{{ $v('youtube_url') }}" maxlength="300" placeholder="https://youtube.com/@channel">{!! $err('youtube_url') !!}</div>
                <div class="ws-f full"><label class="l" for="address">Address</label><input class="input" id="address" name="address" value="{{ $v('address') }}" maxlength="300">{!! $err('address') !!}</div>
            </div>
        </section>

        {{-- Announcement --}}
        <section class="ws-panel" id="ws-announce" data-panel="announce" @if($active !== 'announce') hidden @endif>
            <h2>Announcement bar</h2>
            <p class="lead">A yellow strip at the very top of the website — great for offers and news.</p>
            <div class="ws-toggles" style="grid-template-columns:1fr">{!! $toggle('announcement_on', 'Show announcement bar', 'Visible to every visitor at the top of the page.') !!}</div>
            <div class="ws-announce-prev" aria-hidden="true"><div id="ann-prev">{{ $v('announcement_text') ?: '🎉 এই সপ্তাহে বিশেষ ছাড় — মাত্র ৳{price}!' }}</div><span></span></div>
            <div class="ws-grid">
                <div class="ws-f full"><label class="l" for="announcement_text">Text</label><input class="input" id="announcement_text" name="announcement_text" value="{{ $v('announcement_text') }}" maxlength="200" placeholder="🎉 এই সপ্তাহে বিশেষ ছাড় — মাত্র ৳{price}!"><p class="hint">You can use {price}, {districts}, {upazilas}.</p>{!! $err('announcement_text') !!}</div>
                <div class="ws-f full"><label class="l" for="announcement_link">Link (optional)</label><input class="input" type="url" id="announcement_link" name="announcement_link" value="{{ $v('announcement_link') }}" maxlength="300" placeholder="https://…">{!! $err('announcement_link') !!}</div>
            </div>
        </section>

        {{-- Mobile app --}}
        <section class="ws-panel" id="ws-app" data-panel="app" @if($active !== 'app') hidden @endif>
            <h2>Mobile app</h2>
            <p class="lead">Show “Download the app” buttons on the website. Visitors tap a button and go straight to Google Play or the App Store.</p>
            <div class="ws-toggles" style="grid-template-columns:1fr">{!! $toggle('app_on', 'Show the mobile app on the website', 'Adds a download section, plus optional links in the menu and footer.') !!}</div>

            <div class="ws-stores">
                <div class="ws-store">
                    <div class="head">
                        <span class="badge-mini play"><svg viewBox="0 0 24 24"><path fill="#34a853" d="M4 2.6 13.6 12 4 21.4c-.4-.2-.6-.6-.6-1.1V3.7c0-.5.2-.9.6-1.1z"/><path fill="#fbbc04" d="m16.8 8.8-3.2 3.2 3.2 3.2 3.6-2c.9-.5.9-1.9 0-2.4z"/><path fill="#ea4335" d="M13.6 12 4 21.4c.3.2.8.2 1.2 0l11.6-6.2z"/><path fill="#4285f4" d="M13.6 12 16.8 8.8 5.2 2.6c-.4-.2-.9-.2-1.2 0z"/></svg></span>
                        <div><b>Google Play</b><small>Android phones</small></div>
                        <label class="ws-mini-toggle" title="Show Google Play button"><input type="hidden" name="play_on" value="0"><input type="checkbox" name="play_on" value="1" @checked($on('play_on'))><span class="track"></span></label>
                    </div>
                    <label class="l" for="play_url">Play Store link</label>
                    <div class="ws-url"><input class="input" type="url" id="play_url" name="play_url" value="{{ $v('play_url') }}" maxlength="300" placeholder="https://play.google.com/store/apps/details?id=com.geonexa.app"><a class="btn" id="play_test" href="{{ $v('play_url') ?: '#' }}" target="_blank" rel="noopener" @if(! $v('play_url')) hidden @endif>Test ↗</a></div>
                    {!! $err('play_url') !!}
                </div>
                <div class="ws-store">
                    <div class="head">
                        <span class="badge-mini ios"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.4 12.6c0-2.5 2-3.7 2.1-3.8-1.2-1.7-3-1.9-3.6-2-1.5-.2-3 .9-3.8.9-.8 0-2-.9-3.3-.9-1.7 0-3.3 1-4.2 2.6-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8s2 .8 3.3.8c1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9 0 0-2.8-1-2.8-4.2zM13.9 5.1c.7-.8 1.2-2 1-3.1-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4z"/></svg></span>
                        <div><b>App Store</b><small>iPhone & iPad</small></div>
                        <label class="ws-mini-toggle" title="Show App Store button"><input type="hidden" name="ios_on" value="0"><input type="checkbox" name="ios_on" value="1" @checked($on('ios_on'))><span class="track"></span></label>
                    </div>
                    <label class="l" for="ios_url">App Store link</label>
                    <div class="ws-url"><input class="input" type="url" id="ios_url" name="ios_url" value="{{ $v('ios_url') }}" maxlength="300" placeholder="https://apps.apple.com/app/geonexa/id1234567890"><a class="btn" id="ios_test" href="{{ $v('ios_url') ?: '#' }}" target="_blank" rel="noopener" @if(! $v('ios_url')) hidden @endif>Test ↗</a></div>
                    {!! $err('ios_url') !!}
                </div>
            </div>

            <div class="ws-grid">
                <div class="ws-f"><label class="l" for="app_name">App name</label><input class="input" id="app_name" name="app_name" value="{{ $v('app_name') }}" maxlength="60" data-live="app_name">{!! $err('app_name') !!}</div>
                <div class="ws-f"><label class="l" for="app_rating">Rating (optional)</label><input class="input" id="app_rating" name="app_rating" value="{{ $v('app_rating') }}" maxlength="20" placeholder="4.8" data-live="app_rating">{!! $err('app_rating') !!}</div>
                <div class="ws-f full"><label class="l" for="app_title">Section title</label><input class="input" id="app_title" name="app_title" value="{{ $v('app_title') }}" maxlength="150" data-live="app_title">{!! $err('app_title') !!}</div>
                <div class="ws-f full"><label class="l" for="app_text">Short description</label><textarea class="input" id="app_text" name="app_text" maxlength="400" data-live="app_text">{{ $v('app_text') }}</textarea>{!! $err('app_text') !!}</div>
                <div class="ws-f"><label class="l" for="app_downloads">Downloads text (optional)</label><input class="input" id="app_downloads" name="app_downloads" value="{{ $v('app_downloads') }}" maxlength="40" placeholder="১০ হাজার+ ডাউনলোড" data-live="app_downloads">{!! $err('app_downloads') !!}</div>
            </div>
            <div class="ws-toggles">
                {!! $toggle('app_in_nav', '“অ্যাপ” link in the top menu', 'Jumps to the download section.') !!}
                {!! $toggle('app_in_footer', 'Store buttons in the footer', 'Small Google Play / App Store buttons at the bottom.') !!}
            </div>

            <p class="l" style="font-size:11.5px;font-weight:700;color:var(--muted);margin:4px 0 6px">Live preview</p>
            <div class="ws-app-prev" aria-hidden="true">
                <div class="txt">
                    <span class="name"><img src="{{ $icons['192'] }}" alt="" width="26" height="26"> <b data-out="app_name">{{ $v('app_name') }}</b> <em data-wrap="app_rating" @if(! $v('app_rating')) hidden @endif>★ <span data-out="app_rating">{{ $v('app_rating') }}</span></em> <em data-wrap="app_downloads" @if(! $v('app_downloads')) hidden @endif><span data-out="app_downloads">{{ $v('app_downloads') }}</span></em></span>
                    <h4 data-out="app_title">{{ $v('app_title') }}</h4>
                    <p data-out="app_text">{{ $v('app_text') }}</p>
                    <div class="badges">
                        <span class="sb" data-store="play"><svg viewBox="0 0 24 24"><path fill="#34a853" d="M4 2.6 13.6 12 4 21.4c-.4-.2-.6-.6-.6-1.1V3.7c0-.5.2-.9.6-1.1z"/><path fill="#fbbc04" d="m16.8 8.8-3.2 3.2 3.2 3.2 3.6-2c.9-.5.9-1.9 0-2.4z"/><path fill="#ea4335" d="M13.6 12 4 21.4c.3.2.8.2 1.2 0l11.6-6.2z"/><path fill="#4285f4" d="M13.6 12 16.8 8.8 5.2 2.6c-.4-.2-.9-.2-1.2 0z"/></svg><span><small>GET IT ON</small>Google Play</span></span>
                        <span class="sb" data-store="ios"><svg viewBox="0 0 24 24" fill="#fff"><path d="M16.4 12.6c0-2.5 2-3.7 2.1-3.8-1.2-1.7-3-1.9-3.6-2-1.5-.2-3 .9-3.8.9-.8 0-2-.9-3.3-.9-1.7 0-3.3 1-4.2 2.6-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8s2 .8 3.3.8c1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9 0 0-2.8-1-2.8-4.2zM13.9 5.1c.7-.8 1.2-2 1-3.1-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4z"/></svg><span><small>Download on the</small>App Store</span></span>
                        <span class="none" id="no-store">Add a store link to show buttons</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Sections --}}
        <section class="ws-panel" id="ws-sections" data-panel="sections" @if($active !== 'sections') hidden @endif>
            <h2>Page sections</h2>
            <p class="lead">Show or hide parts of the landing page. Hidden sections also disappear from the menu.</p>
            <div class="ws-toggles">
                {!! $toggle('show_preview', 'Preview (division cards)', '“ভেতরে একনজর” with district lists.') !!}
                {!! $toggle('show_navigate', 'Two-click navigation', 'Checklist section with page mock-ups.') !!}
                {!! $toggle('show_audience', 'Who it is for', 'The 8 audience cards.') !!}
                {!! $toggle('show_pricing_table', 'Division table', 'District & upazila counts beside the price.') !!}
                {!! $toggle('show_faq', 'FAQ', 'Questions and answers.') !!}
                {!! $toggle('show_cta', 'Bottom call-to-action', 'The big green box before the footer.') !!}
            </div>
            <p class="lead">Texts of every section can be edited in <a href="{{ route('landing-content.edit') }}">Landing Page</a>.</p>
        </section>

        {{-- Advanced --}}
        <section class="ws-panel" id="ws-advanced" data-panel="advanced" @if($active !== 'advanced') hidden @endif>
            <h2>Advanced</h2>
            <p class="lead">Fine-tune how the website behaves.</p>
            <div class="ws-toggles">
                {!! $toggle('allow_indexing', 'Show on Google', 'Let search engines index the website (robots.txt + meta).') !!}
                {!! $toggle('track_visitors', 'Count visitors', 'Privacy-friendly stats on the Landing Page screen.') !!}
                {!! $toggle('whatsapp_button', 'Floating WhatsApp button', 'Round green chat button (needs a WhatsApp number).') !!}
                {!! $toggle('mobile_buy_bar', 'Mobile “Order” bar', 'Sticky price + order button on phones.') !!}
                {!! $toggle('show_contact_footer', 'Contact info in footer', 'Phone, email, social links and address.') !!}
                {!! $toggle('show_admin_link', '“Admin” link in footer', 'Turn off to hide the admin login link from visitors.') !!}
            </div>
            <div class="ws-note">🛒 Order price and the “accept orders” switch are in <a href="{{ route('landing-orders.index') }}" style="margin-left:4px">Book Orders → Settings</a>.</div>
        </section>

        <div class="ws-save" id="ws-save">
            <span class="state"><i></i><b id="ws-state">All saved</b></span>
            <button type="submit" class="btn btn-primary"><span data-label>Save settings</span></button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const form = document.getElementById('ws-form'), tabInput = document.getElementById('ws-tab');
    const navBtns = [...document.querySelectorAll('.ws-nav [data-tab]')], panels = [...document.querySelectorAll('[data-panel]')];
    const show = id => {
        navBtns.forEach(b => { const on = b.dataset.tab === id; b.classList.toggle('on', on); b.setAttribute('aria-selected', on); });
        panels.forEach(p => p.hidden = p.dataset.panel !== id);
        tabInput.value = id;
        try { history.replaceState(null, '', '?tab=' + id); } catch (e) {}
    };
    navBtns.forEach(b => b.addEventListener('click', () => show(b.dataset.tab)));

    // Status-specific fields
    const syncStatus = () => {
        const v = (form.querySelector('[name=site_status]:checked') || {}).value;
        document.querySelectorAll('.ws-when').forEach(el => el.hidden = el.dataset.when !== v);
    };
    form.querySelectorAll('[name=site_status]').forEach(r => r.addEventListener('change', syncStatus));
    syncStatus();

    // Colour picker <-> text
    const picker = document.getElementById('theme_picker'), color = document.getElementById('theme_color');
    picker.addEventListener('input', () => { color.value = picker.value; mark(); });
    color.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(color.value)) picker.value = color.value; });
    document.querySelectorAll('[data-color]').forEach(b => b.addEventListener('click', () => { color.value = picker.value = b.dataset.color; mark(); }));

    // Image previews
    form.querySelectorAll('input[type=file][data-preview]').forEach(input => input.addEventListener('change', () => {
        const file = input.files[0];
        document.getElementById(input.id + '-name').textContent = file ? file.name : '';
        if (!file) return;
        if (!/^image\/(png|jpeg)$/.test(file.type)) { alert('Please choose a PNG or JPG image.'); input.value = ''; return; }
        if (file.size > 4 * 1024 * 1024) { alert('Image is larger than 4 MB.'); input.value = ''; return; }
        const url = URL.createObjectURL(file), box = document.getElementById(input.dataset.preview);
        if (input.name === 'logo') box.innerHTML = '<img alt="New logo" src="' + url + '">';
        else box.querySelectorAll('[data-fav]').forEach(img => img.src = url);
        document.getElementById('remove_' + input.name).value = '0';
        mark();
    }));
    form.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => {
        const key = b.dataset.remove;
        document.getElementById('remove_' + key).value = '1';
        const box = document.getElementById(key + '-prev');
        if (key === 'logo') box.innerHTML = '<span class="empty">Logo will be removed when you save</span>';
        else box.querySelectorAll('[data-fav]').forEach(img => img.src = @json(route('brand.file', ['file' => 'icon-192.png'])));
        b.hidden = true; mark();
    }));

    // Announcement live preview
    const ann = document.getElementById('announcement_text'), annPrev = document.getElementById('ann-prev');
    ann.addEventListener('input', () => annPrev.textContent = ann.value || ann.placeholder);

    // Mobile app live preview
    const appBox = document.querySelector('.ws-app-prev');
    const syncApp = () => {
        document.querySelectorAll('[data-live]').forEach(inp => {
            const k = inp.dataset.live, out = appBox.querySelector('[data-out="' + k + '"]'), wrap = appBox.querySelector('[data-wrap="' + k + '"]');
            if (out) out.textContent = inp.value || inp.placeholder || '';
            if (wrap) wrap.hidden = !inp.value.trim();
        });
        let any = false;
        ['play', 'ios'].forEach(k => {
            const url = document.getElementById(k + '_url').value.trim();
            const enabled = document.querySelector('input[type=checkbox][name="' + k + '_on"]').checked;
            const show = enabled && /^https:\/\//.test(url);
            appBox.querySelector('[data-store="' + k + '"]').hidden = !show;
            const test = document.getElementById(k + '_test');
            test.hidden = !/^https:\/\//.test(url); test.href = url || '#';
            any = any || show;
        });
        document.getElementById('no-store').hidden = any;
    };
    document.getElementById('ws-app').addEventListener('input', syncApp);
    document.getElementById('ws-app').addEventListener('change', syncApp);
    syncApp();

    // Unsaved state
    const bar = document.getElementById('ws-save'), state = document.getElementById('ws-state');
    let dirty = false, submitting = false;
    function mark() { dirty = true; bar.classList.add('dirty'); state.textContent = 'Unsaved changes'; }
    form.addEventListener('input', e => { if (e.target.name !== 'tab') mark(); });
    form.addEventListener('change', mark);
    form.addEventListener('submit', () => submitting = true);
    window.addEventListener('beforeunload', e => { if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endpush
