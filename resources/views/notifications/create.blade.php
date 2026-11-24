@extends('layouts.app')

@php
    $audience = old('audience', $preselected->isNotEmpty() ? 'users' : 'all');
    $platform = old('platform', array_key_first(array_filter($platformCounts)) ?? 'Android');
    $selectedIds = collect(old('user_ids', $preselected->all()))->map(fn ($id) => (int) $id)->all();
    $totalReachable = $reachableUsers->count();
    $invalid = fn (string $f) => $errors->has($f) ? 'true' : 'false';
    $tones = ['#2a78d6', '#1baf7a', '#6250d6', '#eb6834', '#e87ba4', '#008300'];
    $tone = fn ($name) => $tones[crc32((string) $name) % count($tones)];
@endphp

@push('head')
<style>
    .aud-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
    .aud { position:relative; display:block; cursor:pointer; }
    .aud input { position:absolute; opacity:0; pointer-events:none; }
    .aud-card { display:grid; gap:3px; height:100%; padding:11px 12px; border:1px solid var(--line); border-radius:11px; background:var(--surface); transition:.15s; }
    .aud-card svg { width:18px; height:18px; color:var(--muted); margin-bottom:3px; }
    .aud-card strong { font-size:12.5px; } .aud-card small { color:var(--muted); font-size:11px; }
    .aud:hover .aud-card { border-color:#c6d0e0; }
    .aud input:checked + .aud-card { border-color:var(--primary); background:var(--primary-soft); box-shadow:0 0 0 1px var(--primary) inset; }
    .aud input:checked + .aud-card svg { color:var(--primary); }
    .aud input:focus-visible + .aud-card { outline:2px solid var(--primary); outline-offset:2px; }
    .aud-panel { margin-top:12px; }
    .user-picker { border:1px solid var(--line); border-radius:11px; overflow:hidden; }
    .picker-bar { display:flex; gap:8px; align-items:center; padding:8px; border-bottom:1px solid var(--line); background:var(--surface-2); flex-wrap:wrap; }
    .picker-bar .input-icon { flex:1 1 200px; }
    .picker-bar .count { font-size:11.5px; color:var(--muted); white-space:nowrap; } .picker-bar .count b { color:var(--ink); }
    .link-btn { border:0; background:none; color:var(--primary); font-weight:700; font-size:11.5px; cursor:pointer; padding:4px; }
    .picker-list { max-height:280px; overflow-y:auto; margin:0; padding:4px; list-style:none; }
    .picker-list label { display:flex; align-items:center; gap:10px; padding:7px 8px; border-radius:8px; cursor:pointer; }
    .picker-list label:hover { background:var(--surface-2); }
    .picker-list input { width:16px; height:16px; accent-color:var(--primary); flex:0 0 16px; }
    .picker-list .av { display:grid; place-items:center; width:30px; height:30px; flex:0 0 30px; border-radius:9px; color:#fff; font-weight:800; font-size:12px; object-fit:cover; }
    .picker-list .who { min-width:0; flex:1; } .picker-list .who strong { display:block; font-size:12.5px; } .picker-list .who span { display:block; color:var(--muted); font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .plat-badge { font-size:10.5px; font-weight:700; color:var(--muted); background:var(--surface-2); border:1px solid var(--line); border-radius:6px; padding:1px 6px; }
    .picker-empty { padding:22px; text-align:center; color:var(--muted); font-size:12px; }
    .img-head { display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .img-switch { display:inline-flex; padding:2px; border:1px solid var(--line); border-radius:9px; background:var(--surface-2); }
    .img-switch button { border:0; background:transparent; color:var(--muted); font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:7px; cursor:pointer; }
    .img-switch button[aria-selected="true"] { background:var(--surface); color:var(--primary); box-shadow:0 1px 3px rgba(0,0,0,.08); }
    .img-drop { position:relative; border:1.5px dashed var(--line); border-radius:12px; background:var(--surface-2); transition:border-color .15s, background .15s; }
    .img-drop:hover, .img-drop.drag { border-color:var(--primary); background:var(--primary-soft); }
    .img-drop input[type=file] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:1; }
    .img-drop.has-file input[type=file] { pointer-events:none; }
    .img-empty { display:grid; place-items:center; gap:3px; padding:20px 14px; text-align:center; color:var(--muted); font-size:12.5px; }
    .img-empty svg { width:28px; height:28px; color:var(--primary); margin-bottom:3px; }
    .img-empty strong { color:var(--primary); } .img-empty small { font-size:11px; }
    .img-chosen { position:relative; z-index:2; display:flex; align-items:center; gap:12px; padding:10px; }
    .img-chosen img { width:96px; height:54px; flex:0 0 96px; object-fit:cover; border-radius:8px; background:var(--surface); }
    .img-chosen div { min-width:0; flex:1; font-size:12.5px; } .img-chosen strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; } .img-chosen span { color:var(--muted); font-size:11.5px; }
    .img-note { display:flex; gap:8px; margin-top:8px; padding:9px 11px; border-radius:10px; background:#fff6e0; color:#7a5200; font-size:11.5px; line-height:1.5; }
    .img-note svg { width:15px; height:15px; flex:0 0 15px; margin-top:1px; } [data-theme="dark"] .img-note { background:#3a2e10; color:#f5c45a; }
    .counter { font-variant-numeric:tabular-nums; }
    .counter.warn { color:#c27c0e; font-weight:700; }

    /* Phone preview */
    .phone { position:relative; margin:0 auto; width:100%; max-width:270px; aspect-ratio:9/15; border-radius:30px; padding:10px; background:#0b1220; box-shadow:0 18px 40px rgba(16,26,49,.25); }
    .phone-screen { height:100%; border-radius:22px; overflow:hidden; background:radial-gradient(circle at 20% 10%,#5b8def 0,transparent 55%),linear-gradient(160deg,#1f3a72,#0f1d3a 60%,#24124a); padding:12px 9px; display:flex; flex-direction:column; gap:8px; }
    .phone-status { display:flex; justify-content:space-between; color:#dbe6ff; font-size:10px; font-weight:700; padding:0 6px; }
    .phone-time { text-align:center; color:#fff; font-size:34px; font-weight:300; letter-spacing:-1px; margin:6px 0 4px; }
    .notif { background:rgba(255,255,255,.88); color:#111827; border-radius:14px; padding:9px 10px; box-shadow:0 6px 18px rgba(0,0,0,.18); backdrop-filter:blur(10px); }
    .notif-head { display:flex; align-items:center; gap:6px; font-size:10px; color:#4b5563; margin-bottom:4px; }
    .notif-app { display:grid; place-items:center; width:16px; height:16px; border-radius:4px; background:linear-gradient(135deg,#62a0ff,#2563eb); color:#fff; font-size:9px; font-weight:800; }
    .notif-head span:last-child { margin-left:auto; }
    .notif strong { display:block; font-size:12px; line-height:1.3; word-break:break-word; }
    .notif p { margin:2px 0 0; font-size:11.5px; line-height:1.35; color:#374151; word-break:break-word; display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden; }
    .notif img { display:block; width:100%; max-height:110px; object-fit:cover; border-radius:8px; margin-top:6px; }
    .reach { display:flex; align-items:center; gap:12px; }
    .reach-num { font-size:26px; font-weight:800; letter-spacing:-.6px; line-height:1; font-variant-numeric:tabular-nums; }
    .reach small { display:block; color:var(--muted); font-size:11.5px; }
    .warn-box { display:flex; gap:9px; padding:11px 13px; border-radius:11px; background:#fff6e0; color:#7a5200; font-size:12.5px; margin-bottom:12px; max-width:1120px; }
    .warn-box svg { width:17px; height:17px; flex:0 0 17px; margin-top:1px; }
    [data-theme="dark"] .warn-box { background:#3a2e10; color:#f5c45a; }
    @media (max-width:760px) { .aud-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:980px) { .phone { max-width:230px; } }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <a class="page-back" href="{{ route('notifications.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 6-6 6 6 6"/></svg>Notifications</a>
            <h1>Send notification</h1>
            <p class="sub">Push a message to app users’ phones via Firebase.</p>
        </div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

@if(! $configured)
    <div class="warn-box" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17v.5"/></svg><div><strong>Firebase is not configured.</strong> Set FIREBASE_CREDENTIALS in .env to the service-account JSON file.</div></div>
@endif
@if($totalReachable === 0)
    <div class="warn-box" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg><div><strong>No user can receive notifications yet.</strong> The mobile app must save each user’s FCM token (field <code>fcm_token</code>) after login. Until then you can use <b>Test token</b> to send to a device directly.</div></div>
@endif
@if($unreachablePreselected->isNotEmpty())
    <div class="warn-box" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg><div>
        @foreach($unreachablePreselected as $u)<strong>{{ $u->name }}</strong> (#{{ $u->id }}) cannot receive notifications — {{ $u->status !== 'active' ? 'account is '.$u->status : 'no device token yet' }}.@if(!$loop->last)<br>@endif @endforeach
    </div></div>
@endif
@if($errors->any())
    <div class="alert-error" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg><div><strong>Please fix {{ $errors->count() === 1 ? 'this problem' : 'these problems' }}:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

<form method="post" action="{{ route('notifications.store') }}" class="form-layout" id="notify-form" enctype="multipart/form-data"
      data-confirm-variant="primary" data-confirm-title="Send this notification?" data-confirm="It will be delivered immediately and cannot be recalled."
      data-confirm-item="" data-confirm-button="Send now" data-confirm-busy="Sending…">
    @csrf
    <div class="form-card">
        {{-- 1. Audience --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">1</span><div><h2>Who should receive it?</h2><p>Only active users whose app registered a device token can receive notifications.</p></div></div>
            <div class="aud-grid" role="radiogroup" aria-label="Audience">
                <label class="aud"><input type="radio" name="audience" value="all" @checked($audience === 'all')><span class="aud-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 4.5a3 3 0 0 1 0 6M21 20a6 6 0 0 0-4-5.6"/></svg><strong>All users</strong><small>{{ number_format($totalReachable) }} devices</small></span></label>
                <label class="aud"><input type="radio" name="audience" value="platform" @checked($audience === 'platform')><span class="aud-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg><strong>By platform</strong><small>Android / iOS / Web</small></span></label>
                <label class="aud"><input type="radio" name="audience" value="users" @checked($audience === 'users')><span class="aud-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><strong>Choose users</strong><small>Pick one or more</small></span></label>
                <label class="aud"><input type="radio" name="audience" value="token" @checked($audience === 'token')><span class="aud-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3"/></svg><strong>Test token</strong><small>Paste a device token</small></span></label>
            </div>

            <div class="aud-panel" data-panel="platform" hidden>
                <div class="segmented" role="radiogroup" aria-label="Platform" style="max-width:420px">
                    @foreach($platforms as $p)
                        <label><input type="radio" name="platform" value="{{ $p }}" @checked($platform === $p) data-count="{{ $platformCounts[$p] ?? 0 }}"><span>{{ $p }} · {{ number_format($platformCounts[$p] ?? 0) }}</span></label>
                    @endforeach
                </div>
            </div>

            <div class="aud-panel" data-panel="users" hidden>
                <div class="user-picker">
                    <div class="picker-bar">
                        <div class="input-icon" style="position:relative">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted)"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="search" class="input" data-user-search placeholder="Search name, email, phone or ID" style="padding-left:32px" aria-label="Search users">
                        </div>
                        <span class="count"><b data-selected-count>0</b> selected</span>
                        <button type="button" class="link-btn" data-select-visible>Select shown</button>
                        <button type="button" class="link-btn" data-clear-selection>Clear</button>
                    </div>
                    @if($reachableUsers->isEmpty())
                        <div class="picker-empty">No users with a device token yet.</div>
                    @else
                        <ul class="picker-list" data-user-list>
                            @foreach($reachableUsers as $u)
                                <li data-search="{{ mb_strtolower($u->name.' '.$u->email.' '.$u->phone.' #'.$u->id.' '.$u->id) }}">
                                    <label>
                                        <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" @checked(in_array($u->id, $selectedIds, true))>
                                        @if($u->photo_url)<img class="av" src="{{ $u->photo_url }}" alt="" loading="lazy">@else<span class="av" style="background:{{ $tone($u->name) }}">{{ $u->initials }}</span>@endif
                                        <span class="who"><strong>{{ $u->name }} <span style="display:inline;color:var(--muted);font-weight:600">#{{ $u->id }}</span></strong><span>{{ $u->email }}{{ $u->phone ? ' · '.$u->phone : '' }}</span></span>
                                        @if($u->platform)<span class="plat-badge">{{ $u->platform }}</span>@endif
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <div class="picker-empty" data-no-match hidden>No users match your search.</div>
                    @endif
                </div>
            </div>

            <div class="aud-panel field" data-panel="token" hidden>
                <label class="field-label" for="test_token">Device FCM token</label>
                <textarea class="input" id="test_token" name="test_token" rows="3" maxlength="4096" spellcheck="false" placeholder="Paste the token printed by the mobile app (FirebaseMessaging.getToken)" style="font-family:ui-monospace,monospace;font-size:12px;min-height:70px" aria-invalid="{{ $invalid('test_token') }}">{{ old('test_token') }}</textarea>
                <span class="hint">Useful for testing before users’ tokens are saved. The full token is not stored in history.</span>
            </div>
        </section>

        {{-- 2. Message --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">2</span><div><h2>Message</h2><p>Short and clear works best — phones show about 2 lines.</p></div></div>
            <div class="form-grid">
                <div class="field span-2">
                    <label class="field-label" for="title">Title <span class="req">*</span></label>
                    <input class="input" id="title" name="title" value="{{ old('title') }}" maxlength="120" required placeholder="e.g. New location data available" aria-invalid="{{ $invalid('title') }}" data-preview="title">
                    <div class="field-foot">@error('title')<span class="field-error">{{ $message }}</span>@else<span></span>@enderror<span class="hint counter" data-counter-for="title" data-max="120">0 / 120</span></div>
                </div>
                <div class="field span-2">
                    <label class="field-label" for="body">Message <span class="req">*</span></label>
                    <textarea class="input" id="body" name="body" rows="3" maxlength="1000" required placeholder="Write the notification text…" aria-invalid="{{ $invalid('body') }}" data-preview="body">{{ old('body') }}</textarea>
                    <div class="field-foot">@error('body')<span class="field-error">{{ $message }}</span>@else<span></span>@enderror<span class="hint counter" data-counter-for="body" data-max="1000" data-soft="180">0 / 1000</span></div>
                </div>
                <div class="field span-2">
                    <div class="img-head">
                        <span class="field-label" id="image-label">Image <span class="opt">optional</span></span>
                        <div class="img-switch" role="tablist" aria-labelledby="image-label">
                            <button type="button" role="tab" data-img-mode="upload" aria-selected="{{ old('image_url') ? 'false' : 'true' }}">Upload</button>
                            <button type="button" role="tab" data-img-mode="url" aria-selected="{{ old('image_url') ? 'true' : 'false' }}">Paste link</button>
                        </div>
                    </div>

                    <div data-img-panel="upload" @if(old('image_url')) hidden @endif>
                        <div class="img-drop" data-img-drop>
                            <input type="file" name="image" id="image" accept="{{ collect($imageTypes)->map(fn ($t) => 'image/'.($t === 'jpg' ? 'jpeg' : $t))->unique()->implode(',') }}" data-img-input data-max-bytes="{{ $imageMaxMb * 1024 * 1024 }}" aria-invalid="{{ $invalid('image') }}">
                            <div class="img-empty" data-img-empty>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8"/></svg>
                                <span><strong>Click to upload</strong> or drag an image here</span>
                                <small>{{ strtoupper(implode(', ', array_diff($imageTypes, ['jpeg']))) }} · up to {{ $imageMaxMb }} MB · wide images (2:1) look best</small>
                            </div>
                            <div class="img-chosen" data-img-chosen hidden>
                                <img alt="Selected image" data-img-thumb>
                                <div><strong data-img-name></strong><span data-img-meta></span></div>
                                <button type="button" class="btn btn-sm" data-img-remove>Remove</button>
                            </div>
                        </div>
                        @error('image')<span class="field-error">{{ $message }}</span>@enderror
                        <span class="field-error" data-img-error hidden></span>
                        @if(! $imagesPublic)
                            <div class="img-note" role="note">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>
                                <span>Phones download the image from this server, so it must be on a <b>public https domain</b>. This panel runs on <code>{{ request()->getSchemeAndHttpHost() }}</code>, which phones cannot reach — the text will arrive but the image will not. Use <b>Paste link</b> with a public image URL until the panel is online.</span>
                            </div>
                        @endif
                    </div>

                    <div data-img-panel="url" @unless(old('image_url')) hidden @endunless>
                        <div class="input-icon" style="position:relative"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted)"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg><input class="input" id="image_url" name="image_url" type="url" value="{{ old('image_url') }}" maxlength="500" placeholder="https://…/banner.jpg" style="padding-left:34px" aria-invalid="{{ $invalid('image_url') }}" data-preview="image"></div>
                        @error('image_url')<span class="field-error">{{ $message }}</span>@else<span class="hint">A public https image link (e.g. from your website or a CDN).</span>@enderror
                    </div>
                </div>
                <div class="field span-2">
                    <label class="field-label" for="link">Open link / screen <span class="opt">optional</span></label>
                    <div class="input-icon" style="position:relative"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted)"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg><input class="input" id="link" name="link" value="{{ old('link') }}" maxlength="500" placeholder="https://… or app://locations/12" style="padding-left:34px" aria-invalid="{{ $invalid('link') }}"></div>
                    @error('link')<span class="field-error">{{ $message }}</span>@else<span class="hint">Sent as <code>data.link</code> for the app to open.</span>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <span class="hint" data-reach-hint></span>
            <a class="btn" href="{{ route('notifications.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit" data-send-button @disabled(! $configured)>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg>
                <span data-label>Send notification</span>
            </button>
        </div>
    </div>

    <aside class="side-stack">
        <div class="form-card side-card">
            <h3>Preview</h3>
            <div class="phone" aria-hidden="true">
                <div class="phone-screen">
                    <div class="phone-status"><span data-clock>9:41</span><span>●●● 5G ▮</span></div>
                    <div class="phone-time" data-clock-big>9:41</div>
                    <div class="notif">
                        <div class="notif-head"><span class="notif-app">G</span><span>GeoNexa</span><span>now</span></div>
                        <strong data-out="title">Notification title</strong>
                        <p data-out="body">Your message will appear here.</p>
                        <img data-out="image" alt="" hidden>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-card side-card">
            <h3>Reach</h3>
            <div class="reach"><span class="reach-num" data-reach>0</span><span><strong data-reach-label>devices</strong><small data-reach-sub>will receive this notification</small></span></div>
        </div>
    </aside>
</form>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const form = document.getElementById('notify-form');
    const total = {{ (int) $totalReachable }};
    const radios = form.querySelectorAll('input[name="audience"]');
    const panels = form.querySelectorAll('[data-panel]');
    const reachEl = form.querySelector('[data-reach]');
    const reachLabel = form.querySelector('[data-reach-label]');
    const reachSub = form.querySelector('[data-reach-sub]');
    const reachHint = form.querySelector('[data-reach-hint]');
    const sendBtn = form.querySelector('[data-send-button]');
    const audience = () => form.querySelector('input[name="audience"]:checked')?.value || 'all';
    const checked = () => form.querySelectorAll('input[name="user_ids[]"]:checked').length;

    const reach = () => {
        switch (audience()) {
            case 'platform': { const p = form.querySelector('input[name="platform"]:checked'); return [Number(p?.dataset.count || 0), (p?.value || '') + ' devices']; }
            case 'users': return [checked(), 'selected users'];
            case 'token': return [form.querySelector('#test_token').value.trim() ? 1 : 0, 'test device'];
            default: return [total, 'devices'];
        }
    };
    const update = () => {
        const a = audience();
        panels.forEach((p) => { p.hidden = p.dataset.panel !== a; });
        form.querySelector('#test_token').required = a === 'token';
        const [n, label] = reach();
        reachEl.textContent = n.toLocaleString();
        reachLabel.textContent = n === 1 ? label.replace(/s$/, '').replace('devices', 'device') : label;
        reachSub.textContent = n === 0 ? 'Nobody will receive this yet' : 'will receive this notification';
        reachHint.textContent = n === 0 ? '' : `Will be sent to ${n.toLocaleString()} ${n === 1 ? 'device' : 'devices'}`;
        form.dataset.confirmItem = `${n.toLocaleString()} ${n === 1 ? 'recipient' : 'recipients'} · ${a === 'all' ? 'all active users' : a === 'platform' ? label : a === 'users' ? 'selected users' : 'test token'}`;
        const sc = form.querySelector('[data-selected-count]'); if (sc) sc.textContent = checked();
    };
    radios.forEach((r) => r.addEventListener('change', update));
    form.querySelectorAll('input[name="platform"], input[name="user_ids[]"]').forEach((el) => el.addEventListener('change', update));
    form.querySelector('#test_token').addEventListener('input', update);

    // Block sending when the audience is empty (runs on click, before the confirm dialog).
    sendBtn.addEventListener('click', (e) => {
        if (reach()[0] > 0) return;
        e.preventDefault();
        const a = audience();
        alertMsg(a === 'users' ? 'Please choose at least one user.' : a === 'token' ? 'Please paste a device token.' : 'Nobody in this audience can receive notifications yet.');
    });
    const alertMsg = (msg) => { reachHint.textContent = msg; reachHint.style.color = '#d03b3b'; setTimeout(() => { reachHint.style.color = ''; update(); }, 2500); };

    // User picker search / bulk select
    const search = form.querySelector('[data-user-search]');
    const items = [...form.querySelectorAll('[data-user-list] li')];
    const noMatch = form.querySelector('[data-no-match]');
    search?.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        let shown = 0;
        items.forEach((li) => { const hit = !q || li.dataset.search.includes(q); li.hidden = !hit; if (hit) shown++; });
        if (noMatch) noMatch.hidden = shown > 0;
    });
    form.querySelector('[data-select-visible]')?.addEventListener('click', () => { items.filter((li) => !li.hidden).forEach((li) => { li.querySelector('input').checked = true; }); update(); });
    form.querySelector('[data-clear-selection]')?.addEventListener('click', () => { items.forEach((li) => { li.querySelector('input').checked = false; }); update(); });

    // Live preview + counters
    const out = (k) => form.querySelector(`[data-out="${k}"]`);
    const title = form.querySelector('#title'), body = form.querySelector('#body'), image = form.querySelector('#image_url');

    // Image: upload a file or paste a link (only one is submitted)
    let uploadedUrl = null;
    let mode = form.querySelector('[data-img-mode][aria-selected="true"]')?.dataset.imgMode || 'upload';
    const fileInput = form.querySelector('[data-img-input]');
    const drop = form.querySelector('[data-img-drop]');
    const chosen = form.querySelector('[data-img-chosen]');
    const empty = form.querySelector('[data-img-empty]');
    const imgError = form.querySelector('[data-img-error]');
    const allowedTypes = fileInput.accept.split(',');
    const maxBytes = Number(fileInput.dataset.maxBytes);
    const setImgError = (m) => { imgError.textContent = m; imgError.hidden = !m; };
    const clearUpload = () => {
        if (uploadedUrl) URL.revokeObjectURL(uploadedUrl);
        uploadedUrl = null;
        fileInput.value = '';
        chosen.hidden = true; empty.hidden = false; drop.classList.remove('has-file');
    };
    const useFile = (file) => {
        setImgError('');
        if (!file) return;
        if (!allowedTypes.includes(file.type)) { clearUpload(); return setImgError('Please choose a ' + allowedTypes.map((t) => t.replace('image/', '').toUpperCase()).join(' or ') + ' image.'); }
        if (file.size > maxBytes) { clearUpload(); return setImgError(`This image is ${(file.size / 1048576).toFixed(1)} MB. The limit is ${Math.round(maxBytes / 1048576)} MB.`); }
        if (uploadedUrl) URL.revokeObjectURL(uploadedUrl);
        uploadedUrl = URL.createObjectURL(file);
        form.querySelector('[data-img-thumb]').src = uploadedUrl;
        form.querySelector('[data-img-name]').textContent = file.name;
        form.querySelector('[data-img-meta]').textContent = `${(file.size / 1024).toFixed(0)} KB · will be optimised before sending`;
        chosen.hidden = false; empty.hidden = true; drop.classList.add('has-file');
        preview();
    };
    fileInput.addEventListener('change', () => useFile(fileInput.files[0]));
    ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, () => drop.classList.add('drag')));
    ['dragleave', 'drop'].forEach((t) => drop.addEventListener(t, () => drop.classList.remove('drag')));
    form.querySelector('[data-img-remove]').addEventListener('click', () => { clearUpload(); setImgError(''); preview(); });
    form.querySelectorAll('[data-img-mode]').forEach((btn) => btn.addEventListener('click', () => {
        mode = btn.dataset.imgMode;
        form.querySelectorAll('[data-img-mode]').forEach((b) => b.setAttribute('aria-selected', String(b === btn)));
        form.querySelectorAll('[data-img-panel]').forEach((p) => { p.hidden = p.dataset.imgPanel !== mode; });
        if (mode === 'url') { clearUpload(); setImgError(''); } else { image.value = ''; }
        preview();
    }));
    const preview = () => {
        out('title').textContent = title.value.trim() || 'Notification title';
        out('body').textContent = body.value.trim() || 'Your message will appear here.';
        const url = uploadedUrl || (mode === 'url' ? image.value.trim() : '');
        const img = out('image');
        if (uploadedUrl || /^https:\/\/\S+$/i.test(url)) { img.src = url; img.hidden = false; } else { img.hidden = true; img.removeAttribute('src'); }
        form.querySelectorAll('[data-counter-for]').forEach((c) => {
            const field = form.querySelector('#' + c.dataset.counterFor);
            const len = field.value.length;
            c.textContent = `${len} / ${c.dataset.max}`;
            c.classList.toggle('warn', c.dataset.soft ? len > Number(c.dataset.soft) : len > Number(c.dataset.max) * 0.9);
            if (c.dataset.soft) c.title = len > Number(c.dataset.soft) ? 'Long messages are cut off on most lock screens' : '';
        });
    };
    out('image').addEventListener('error', () => { out('image').hidden = true; });
    [title, body, image].forEach((el) => el.addEventListener('input', preview));

    const clock = () => { const t = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }).replace(/\s?[AP]M/i, ''); form.querySelectorAll('[data-clock], [data-clock-big]').forEach((el) => { el.textContent = t; }); };
    clock();
    update();
    preview();
})();
</script>
@endpush
