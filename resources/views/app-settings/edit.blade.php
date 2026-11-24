@extends('layouts.app')

@push('head')
<style>
    .settings-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(280px,.7fr); gap:14px; align-items:start; }
    .setting-card { padding:18px; background:var(--surface); border:1px solid var(--line); border-radius:14px; box-shadow:var(--shadow); }
    .setting-card + .setting-card { margin-top:14px; }
    .setting-head { display:flex; align-items:flex-start; gap:11px; margin-bottom:16px; }
    .setting-icon { display:grid; place-items:center; width:34px; height:34px; flex:0 0 34px; border-radius:10px; background:var(--primary-soft); color:var(--primary); }
    .setting-icon svg { width:17px; height:17px; }
    .setting-head h2 { margin:0; font-size:14px; } .setting-head p { margin:3px 0 0; color:var(--muted); font-size:11.5px; }
    .switch-list { display:grid; gap:8px; }
    .switch-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:11px 12px; border:1px solid var(--line); border-radius:10px; background:var(--surface-2); }
    .switch-row strong { display:block; font-size:12px; } .switch-row small { display:block; margin-top:2px; color:var(--muted); font-size:11px; }
    .switch { position:relative; width:40px; height:22px; flex:0 0 40px; } .switch input { position:absolute; opacity:0; } .switch span { position:absolute; inset:0; border-radius:999px; background:#aeb8c8; cursor:pointer; transition:.2s; } .switch span::after { content:""; position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; transition:.2s; box-shadow:0 1px 3px #0003; } .switch input:checked + span { background:var(--primary); } .switch input:checked + span::after { transform:translateX(18px); }
    .danger-card { border-color:#f1c5c5; } [data-theme="dark"] .danger-card { border-color:#63323a; } .danger-card .setting-icon { background:#fdecec; color:#c62828; } [data-theme="dark"] .danger-card .setting-icon { background:#3a1b1f; color:#ff9a9a; }
    .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; } .field.full { grid-column:1/-1; } .field label { display:block; margin-bottom:5px; color:var(--muted); font-size:11px; font-weight:700; } .input { width:100%; }
    .settings-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:15px; } .hint-box { padding:11px 12px; border-radius:10px; background:var(--primary-soft); color:var(--muted); font-size:11px; line-height:1.55; }
    @media (max-width:760px) { .settings-grid { grid-template-columns:1fr; } } @media (max-width:520px) { .form-grid { grid-template-columns:1fr; } .field.full { grid-column:auto; } }
</style>
@endpush

@section('content')
<header class="topbar">
    <div><p class="eyebrow">Mobile control center</p><h1>App Settings</h1><p class="sub">Control security, maintenance and app availability from one place.</p></div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

@if(session('success'))<div class="page-flash">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="post" action="{{ route('app-settings.update') }}">
    @csrf @method('PUT')
    <div class="settings-grid">
        <div>
            <section class="setting-card">
                <div class="setting-head"><span class="setting-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 17h6M9 7h6"/></svg></span><div><h2>App security &amp; media</h2><p>These flags are returned to the mobile app as true/false controls.</p></div></div>
                <div class="switch-list">
                    @foreach([
                        'screen_lock_enabled' => ['Screen lock', 'Require the app lock screen when the app opens.'],
                        'screenshot_enabled' => ['Screenshots allowed', 'Allow screenshots and screen capture.'],
                        'screen_recording_enabled' => ['Screen recording allowed', 'Allow screen recording on supported devices.'],
                        'short_video_enabled' => ['Short video enabled', 'Allow short video/reels content in the app.'],
                    ] as $field => [$label, $help])
                        <label class="switch-row"><span><strong>{{ $label }}</strong><small>{{ $help }}</small></span><span class="switch"><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" @checked($settings->{$field})><span></span></span></label>
                    @endforeach
                </div>
            </section>
            <section class="setting-card">
                <div class="setting-head"><span class="setting-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/></svg></span><div><h2>Maintenance &amp; version</h2><p>Show a controlled maintenance state and enforce app upgrades.</p></div></div>
                <div class="switch-list">
                    <label class="switch-row"><span><strong>Server down / maintenance mode</strong><small>Protected app APIs return HTTP 503 with the maintenance message.</small></span><span class="switch"><input type="hidden" name="server_down" value="0"><input type="checkbox" name="server_down" value="1" @checked($settings->server_down)><span></span></span></label>
                    <label class="switch-row"><span><strong>Force update</strong><small>Tell the app that an update is required.</small></span><span class="switch"><input type="hidden" name="force_update" value="0"><input type="checkbox" name="force_update" value="1" @checked($settings->force_update)><span></span></span></label>
                </div>
                <div class="form-grid" style="margin-top:13px">
                    <div class="field"><label for="minimum_app_version">Minimum app version</label><input class="input" id="minimum_app_version" name="minimum_app_version" value="{{ old('minimum_app_version', $settings->minimum_app_version) }}" placeholder="e.g. 1.2.0" maxlength="30"></div>
                    <div class="field"><label for="maintenance_message">Maintenance message</label><input class="input" id="maintenance_message" name="maintenance_message" value="{{ old('maintenance_message', $settings->maintenance_message) }}" placeholder="We will be back soon" maxlength="500"></div>
                </div>
            </section>
        </div>
        <aside>
            <section class="setting-card">
                <div class="setting-head"><span class="setting-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v14H4z"/><path d="m8 10 3 3 5-5"/></svg></span><div><h2>Support details</h2><p>Returned in the public app-settings API.</p></div></div>
                <div class="form-grid">
                    <div class="field full"><label for="support_phone">Support phone</label><input class="input" id="support_phone" name="support_phone" value="{{ old('support_phone', $settings->support_phone) }}" maxlength="30" placeholder="01700000000"></div>
                    <div class="field full"><label for="support_email">Support email</label><input class="input" id="support_email" name="support_email" type="email" value="{{ old('support_email', $settings->support_email) }}" maxlength="190" placeholder="support@example.com"></div>
                    <div class="field full"><label for="bkash_merchant_number">bKash merchant / personal number</label><input class="input" id="bkash_merchant_number" name="bkash_merchant_number" value="{{ old('bkash_merchant_number', $settings->bkash_merchant_number) }}" maxlength="20" inputmode="numeric" placeholder="01700000000"><small style="display:block;margin-top:4px;color:var(--muted);font-size:11px">Shown in the mobile app for paid package payments.</small></div>
                </div>
            </section>
            <section class="setting-card danger-card">
                <div class="setting-head"><span class="setting-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M8 7l4-4 4 4"/><path d="M5 12a7 7 0 1 0 14 0"/></svg></span><div><h2>Emergency action</h2><p>Use only when every mobile session must be closed.</p></div></div>
                <label class="switch-row"><span><strong>Force logout all users</strong><small>Immediately revokes every app access and refresh token.</small></span><span class="switch"><input type="checkbox" name="force_logout" value="1"><span></span></span></label>
                <div class="hint-box" style="margin-top:10px">Users must log in again after this action. This action does not delete accounts or subscriptions.</div>
            </section>
        </aside>
    </div>
    <div class="settings-actions"><button class="btn btn-primary" type="submit">Save app settings</button></div>
</form>
@endsection
