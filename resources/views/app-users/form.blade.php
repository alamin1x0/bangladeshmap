@extends('layouts.app')

@php
    $editing = (bool) $appUser;
    $status = old('status', $appUser?->status ?? 'active');
    $accountType = old('account_type', $appUser?->account_type ?? 'free');
    $packageStatus = old('package_status', $appUser?->package_status);
    $trialState = $appUser?->subscriptionState();
    $platform = old('platform', $appUser?->platform);
    $platformOptions = ['Android', 'iOS', 'Web'];
    $deviceFields = ['platform', 'app_version', 'device_model', 'fcm_token'];
    $deviceOpen = $errors->hasAny($deviceFields) || collect($deviceFields)->contains(fn ($f) => filled(old($f, $appUser?->{$f})));
    $invalid = fn (string $field) => $errors->has($field) ? 'true' : 'false';
    $photoUrl = old('remove_photo') ? null : $appUser?->photo_url;
    $photoTypes = \App\Support\ProfilePhoto::acceptedExtensions();
    $photoMaxMb = (int) (\App\Support\ProfilePhoto::MAX_KB / 1024);
    $initial = mb_strtoupper(mb_substr(old('name', $appUser?->name) ?: '?', 0, 1));
@endphp

@push('head')
<style>
    /* Profile preview card */
    .profile { overflow:hidden; }
    .profile-banner { height:64px; background:radial-gradient(circle at 85% 0%,#7fb4ff66,transparent 55%),linear-gradient(135deg,#1f4fbf,#2a78d6); }
    .profile-main { display:flex; flex-direction:column; align-items:center; text-align:center; gap:4px; padding:0 16px 14px; margin-top:-30px; }
    .big-avatar { display:grid; place-items:center; width:60px; height:60px; border-radius:18px; border:3px solid var(--surface); background:linear-gradient(135deg,#62a0ff,#2563eb); color:#fff; font-size:22px; font-weight:800; box-shadow:0 8px 20px #2563eb40; margin-bottom:4px; }
    .profile-main strong { font-size:14.5px; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .profile-main .mail { color:var(--muted); font-size:12px; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .status-pill { display:inline-flex; align-items:center; gap:6px; margin-top:6px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:var(--surface-2); color:var(--muted); }
    .profile-rows { margin:0; padding:4px 16px 12px; display:grid; border-top:1px solid var(--line); }
    .profile-rows div { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:7px 0; font-size:12px; }
    .profile-rows div + div { border-top:1px dashed var(--line); }
    .profile-rows dt { color:var(--muted); display:flex; align-items:center; gap:7px; }
    .profile-rows dt svg { width:14px; height:14px; }
    .profile-rows dd { margin:0; font-weight:600; text-align:right; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .profile-rows a { color:var(--primary); text-decoration:none; }

    .title-id { display:inline-block; margin-left:6px; padding:2px 8px; border-radius:7px; background:var(--surface-2); border:1px solid var(--line); font:700 12px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--muted); vertical-align:middle; letter-spacing:0; }
    .id-copy { display:inline-flex; align-items:center; gap:5px; padding:2px 7px; border:1px solid var(--line); border-radius:6px; background:var(--surface-2); color:var(--ink); font:700 11.5px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace; cursor:pointer; }
    .id-copy svg { width:12px; height:12px; color:var(--muted); } .id-copy:hover { border-color:var(--primary); } .id-copy.copied { border-color:#0ca30c; color:#00720a; }
    .muted-id { color:var(--muted); font-weight:500; font-size:11.5px; }

    /* Leading icons inside inputs */
    .input-icon { position:relative; }
    .input-icon > svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--muted); pointer-events:none; transition:color .15s; }
    .input-icon > .input { padding-left:34px; }
    .input-icon:focus-within > svg { color:var(--primary); }

    .big-avatar { overflow:hidden; }
    .big-avatar img { width:100%; height:100%; object-fit:cover; display:block; }

    /* Profile photo uploader */
    .photo-field { display:flex; align-items:center; gap:16px; margin-bottom:16px; padding:12px; border:1px dashed var(--line); border-radius:12px; background:var(--surface-2); transition:border-color .15s, background .15s; }
    .photo-field.drag { border-color:var(--primary); background:var(--primary-soft); }
    .photo-drop { position:relative; display:grid; place-items:center; width:76px; height:76px; flex:0 0 76px; border-radius:22px; background:linear-gradient(135deg,#62a0ff,#2563eb); color:#fff; font-size:28px; font-weight:800; cursor:pointer; box-shadow:0 8px 18px #2563eb33; outline:none; }
    .photo-drop img { width:100%; height:100%; object-fit:cover; border-radius:inherit; }
    .photo-drop:focus-visible { box-shadow:0 0 0 3px var(--surface), 0 0 0 5px var(--primary); }
    .photo-hover { position:absolute; inset:0; display:grid; place-items:center; border-radius:inherit; background:rgba(7,17,39,.45); color:#fff; opacity:0; transition:opacity .15s; }
    .photo-hover svg { width:24px; height:24px; }
    .photo-drop:hover .photo-hover, .photo-drop:focus-visible .photo-hover { opacity:1; }
    .photo-badge { position:absolute; right:-5px; bottom:-5px; display:grid; place-items:center; width:26px; height:26px; border-radius:50%; background:var(--primary); color:#fff; border:3px solid var(--surface-2); }
    .photo-badge svg { width:12px; height:12px; }
    .photo-info { display:grid; gap:5px; min-width:0; }
    .photo-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:2px; }
    .btn-sm { padding:5px 11px; font-size:12px; border-radius:8px; }
    .btn-sm svg { width:14px; height:14px; }
    .btn-remove:hover { color:#d03b3b; border-color:#d03b3b; }
    .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    @media (max-width:650px) { .photo-field { gap:12px; } .photo-drop { width:64px; height:64px; flex-basis:64px; border-radius:18px; font-size:24px; } }

    /* Platform picker */
    .platforms span svg { width:14px; height:14px; }
    details.device > summary { list-style:none; cursor:pointer; }
    details.device > summary::-webkit-details-marker { display:none; }
    details.device > summary .chev { width:18px; height:18px; color:var(--muted); margin-left:auto; transition:transform .2s; }
    details.device[open] > summary .chev { transform:rotate(180deg); }
    details.device[open] > summary { margin-bottom:16px; }
    details.device > summary.form-section-head { margin-bottom:0; }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <a class="page-back" href="{{ route('app-users.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 6-6 6 6 6"/></svg>App users</a>
            <h1>{{ $editing ? 'Edit app user' : 'Add app user' }}@if($editing) <span class="title-id">#{{ $appUser->id }}</span>@endif</h1>
            <p class="sub">{{ $editing ? 'Update account details. Leave the PIN empty to keep it.' : 'Create a mobile app account in three quick steps.' }}</p>
        </div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

@if(session('error'))
    <div class="alert-error" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert-error" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>
        <div><strong>Please fix {{ $errors->count() === 1 ? 'this problem' : 'these '.$errors->count().' problems' }}:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    </div>
@endif

<form method="post" action="{{ $editing ? route('app-users.update', $appUser) : route('app-users.store') }}" class="form-layout" enctype="multipart/form-data" data-submit-once autocomplete="off">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="form-card">
        {{-- 1. Account --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">1</span><div><h2>Account details</h2><p>Who is this user and how can we reach them?</p></div></div>

            {{-- Profile photo --}}
            <div class="photo-field">
                <div class="photo-drop {{ $photoUrl ? 'has-photo' : '' }}" data-photo-drop tabindex="0" role="button" aria-label="Upload profile photo" aria-describedby="photo-help">
                    <img src="{{ $photoUrl ?? '' }}" alt="Profile photo" data-photo-img @if(!$photoUrl) hidden @endif>
                    <span class="photo-initial" data-photo-initial @if($photoUrl) hidden @endif>{{ $initial }}</span>
                    <span class="photo-hover" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg></span>
                    <span class="photo-badge" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>
                </div>
                <div class="photo-info">
                    <span class="field-label">Profile photo <span class="opt">optional</span></span>
                    <span class="hint" id="photo-help">{{ strtoupper(implode(', ', array_diff($photoTypes, ['jpeg']))) }} · up to {{ $photoMaxMb }} MB. Cropped to a square automatically.</span>
                    <div class="photo-actions">
                        <label class="btn btn-sm" for="photo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg><span data-photo-pick-label>{{ $photoUrl ? 'Change photo' : 'Upload photo' }}</span></label>
                        <button type="button" class="btn btn-sm btn-remove" data-photo-remove @if(!$photoUrl) hidden @endif>Remove</button>
                        <button type="button" class="btn btn-sm" data-photo-undo hidden>Undo</button>
                    </div>
                    @error('photo')<span class="field-error">{{ $message }}</span>@enderror
                    @if($errors->any() && ! $errors->has('photo'))<span class="hint" data-photo-reselect>If you had chosen a new photo, please select it again.</span>@endif
                    <span class="field-error" data-photo-error hidden></span>
                </div>
                <input type="file" id="photo" name="photo" class="sr-only" accept="{{ collect($photoTypes)->map(fn ($t) => 'image/'.($t === 'jpg' ? 'jpeg' : $t))->unique()->implode(',') }}" data-photo-input data-max-bytes="{{ $photoMaxMb * 1024 * 1024 }}">
                <input type="hidden" name="remove_photo" value="{{ old('remove_photo', 0) }}" data-photo-remove-flag>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label class="field-label" for="name">Full name <span class="req">*</span></label>
                    <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><input class="input" id="name" name="name" value="{{ old('name', $appUser?->name) }}" placeholder="e.g. Rahim Uddin" maxlength="150" required autocomplete="off" aria-invalid="{{ $invalid('name') }}" data-preview="name"></div>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="email">Email <span class="req">*</span></label>
                    <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg><input class="input" id="email" name="email" type="email" value="{{ old('email', $appUser?->email) }}" placeholder="user@example.com" maxlength="190" required autocomplete="off" inputmode="email" aria-invalid="{{ $invalid('email') }}" data-preview="email"></div>
                    @error('email')<span class="field-error">{{ $message }}</span>@else<span class="hint">Used to sign in to the app. Must be unique.</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="phone">Phone <span class="opt">optional</span></label>
                    <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg><input class="input" id="phone" name="phone" type="tel" value="{{ old('phone', $appUser?->phone) }}" placeholder="01XXXXXXXXX" maxlength="30" inputmode="tel" autocomplete="off" aria-invalid="{{ $invalid('phone') }}"></div>
                    @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <span class="field-label" id="status-label">Status <span class="req">*</span></span>
                    <div class="segmented" role="radiogroup" aria-labelledby="status-label">
                        @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'blocked' => 'Blocked'] as $value => $label)
                            <label><input type="radio" name="status" value="{{ $value }}" @checked($status === $value) required data-preview="status"><span><i class="dot {{ $value }}"></i>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('status')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- 2. Subscription --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">2</span><div><h2>Account &amp; subscription</h2><p>Set the user's access tier and current package details.</p></div></div>
            <div class="form-grid">
                <div class="field">
                    <span class="field-label" id="account-type-label">Account type <span class="req">*</span></span>
                    <div class="segmented" role="radiogroup" aria-labelledby="account-type-label">
                        @foreach(['free' => 'FREE', 'paid' => 'PAID', 'trial' => 'TRIAL'] as $value => $label)
                            <label><input type="radio" name="account_type" value="{{ $value }}" @checked($accountType === $value) required><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('account_type')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="subscription_id">Subscription ID <span class="opt">optional</span></label>
                    <input class="input" id="subscription_id" name="subscription_id" value="{{ old('subscription_id', $appUser?->subscription_id) }}" maxlength="190" placeholder="Payment provider subscription ID" aria-invalid="{{ $invalid('subscription_id') }}">
                    @error('subscription_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="subscription_package_name">Package name</label>
                    <input class="input" id="subscription_package_name" name="subscription_package_name" value="{{ old('subscription_package_name', $appUser?->subscription_package_name) }}" maxlength="120" placeholder="e.g. Premium monthly">
                    @error('subscription_package_name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="subscription_package_id">Package ID</label>
                    <input class="input" id="subscription_package_id" name="subscription_package_id" value="{{ old('subscription_package_id', $appUser?->subscription_package_id) }}" maxlength="100" placeholder="e.g. premium_monthly">
                    @error('subscription_package_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="subscription_package_type">Package type</label>
                    <input class="input" id="subscription_package_type" name="subscription_package_type" value="{{ old('subscription_package_type', $appUser?->subscription_package_type) }}" maxlength="50" placeholder="Monthly / yearly">
                    @error('subscription_package_type')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="package_status">Package status</label>
                    <select class="input" id="package_status" name="package_status">
                        <option value="">Not subscribed</option>
                        @foreach(['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected($packageStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('package_status')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="package_start_date">Package start date</label>
                    <input class="input" id="package_start_date" name="package_start_date" type="date" value="{{ old('package_start_date', optional($appUser?->package_start_date)->format('Y-m-d')) }}">
                    @error('package_start_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="package_end_date">Package end date</label>
                    <input class="input" id="package_end_date" name="package_end_date" type="date" value="{{ old('package_end_date', optional($appUser?->package_end_date)->format('Y-m-d')) }}">
                    @error('package_end_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <label class="field" style="display:flex;align-items:center;gap:9px;padding-top:29px"><input type="hidden" name="package_auto_renew" value="0"><input type="checkbox" name="package_auto_renew" value="1" @checked(old('package_auto_renew', $appUser?->package_auto_renew))> Auto renew package</label>
            </div>
            @if($editing)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;padding:12px;border:1px solid var(--line);border-radius:11px;background:var(--surface-2)">
                    <div style="min-width:0">
                        <strong style="font-size:12px">14-day trial</strong>
                        <div style="color:var(--muted);font-size:11.5px;margin-top:3px">
                            @if($appUser->trialAlreadyUsed())
                                Used: {{ optional($appUser->trial_started_at)->format('d M Y') }} – {{ optional($appUser->trial_ends_at)->format('d M Y') }}
                                @if($trialState === 'active') · {{ now()->startOfDay()->diffInDays($appUser->trial_ends_at) + 1 }} days remaining @endif
                            @else
                                This account has not used its trial.
                            @endif
                        </div>
                    </div>
                    @if(!$appUser->trialAlreadyUsed() && !$appUser->hasActiveSubscription())
                        <button class="btn btn-sm" type="submit" form="start-trial-form" data-confirm="Start a 14-day trial for this user?">Start trial</button>
                    @endif
                </div>
            @endif
        </section>

        {{-- 3. PIN --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">3</span><div><h2>Login PIN</h2><p>{{ $editing ? 'Only fill this in to set a new 6-digit PIN.' : 'Use exactly 6 numbers. The PIN is stored securely as a one-way hash.' }}</p></div></div>
            <div class="form-grid">
                <div class="field span-2">
                    <label class="field-label" for="pin">{{ $editing ? 'New 6-digit PIN' : '6-digit PIN' }} @if($editing)<span class="opt">optional</span>@else<span class="req">*</span>@endif</label>
                    <div class="input-group">
                        <input class="input" id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" placeholder="{{ $editing ? 'Leave empty to keep current PIN' : 'Enter 6 numbers' }}" autocomplete="new-password" @if(!$editing) required @endif aria-invalid="{{ $invalid('pin') }}" aria-describedby="pin-help">
                        <span class="input-addons">
                            <button type="button" class="input-addon" data-generate title="Generate a 6-digit PIN" aria-label="Generate a 6-digit PIN"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg></button>
                            <button type="button" class="input-addon" data-reveal title="Show PIN" aria-label="Show PIN" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </span>
                    </div>
                    <div class="meter" data-meter data-score="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    @error('pin')<span class="field-error">{{ $message }}</span>@else<span class="hint" id="pin-help" data-strength>Only 6 numbers are allowed.</span>@enderror
                </div>
            </div>
        </section>

        {{-- 4. Device (optional, collapsible) --}}
        <details class="form-section device" @if($deviceOpen) open @endif>
            <summary class="form-section-head">
                <span class="step">4</span>
                <div><h2>Device &amp; app info <span class="opt">optional</span></h2><p>Usually filled automatically by the mobile app.</p></div>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="form-grid">
                <div class="field">
                    <span class="field-label" id="platform-label">Platform</span>
                    @php
                        $platformIcons = [
                            'Android' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 9.5h10V17a1.5 1.5 0 0 1-1.5 1.5H15v2.3a1.2 1.2 0 0 1-2.4 0v-2.3h-1.2v2.3a1.2 1.2 0 0 1-2.4 0v-2.3H8.5A1.5 1.5 0 0 1 7 17zM4.8 9.5A1.2 1.2 0 0 1 6 10.7v4.6a1.2 1.2 0 0 1-2.4 0v-4.6a1.2 1.2 0 0 1 1.2-1.2zm14.4 0a1.2 1.2 0 0 1 1.2 1.2v4.6a1.2 1.2 0 0 1-2.4 0v-4.6a1.2 1.2 0 0 1 1.2-1.2zM8.6 4.2l-.9-1.4a.3.3 0 0 1 .5-.3l.9 1.4a6 6 0 0 1 5.8 0l.9-1.4a.3.3 0 0 1 .5.3l-.9 1.4A4.9 4.9 0 0 1 17 8.7H7a4.9 4.9 0 0 1 1.6-4.5zM10 6.5a.6.6 0 1 0 0-1.2.6.6 0 0 0 0 1.2zm4 0a.6.6 0 1 0 0-1.2.6.6 0 0 0 0 1.2z"/></svg>',
                            'iOS' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.4 12.6c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.2-2.8.8-3.5.8-.7 0-1.8-.8-3-.8-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.3.9-1.3 1.3-2.5 1.3-2.6 0 0-2.5-1-2.5-3.7zM14.2 5.8c.6-.8 1.1-1.8 1-2.8-.9 0-2 .6-2.7 1.4-.6.7-1.1 1.7-1 2.7 1 .1 2-.5 2.7-1.3z"/></svg>',
                            'Web' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>',
                        ];
                        $customPlatform = filled($platform) && ! in_array(strtolower($platform), array_map('strtolower', $platformOptions), true);
                    @endphp
                    <div class="segmented platforms" role="radiogroup" aria-labelledby="platform-label">
                        <label><input type="radio" name="platform" value="" @checked(blank($platform)) data-preview="platform"><span>None</span></label>
                        @foreach($platformOptions as $option)
                            <label><input type="radio" name="platform" value="{{ $option }}" @checked(strcasecmp((string) $platform, $option) === 0) data-preview="platform"><span>{!! $platformIcons[$option] !!}{{ $option }}</span></label>
                        @endforeach
                        @if($customPlatform)<label><input type="radio" name="platform" value="{{ $platform }}" checked data-preview="platform"><span>{{ $platform }}</span></label>@endif
                    </div>
                    @error('platform')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="app_version">App version</label>
                    <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.3"/></svg><input class="input" id="app_version" name="app_version" value="{{ old('app_version', $appUser?->app_version) }}" placeholder="e.g. 1.0.0" maxlength="30" aria-invalid="{{ $invalid('app_version') }}"></div>
                    @error('app_version')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field span-2">
                    <label class="field-label" for="device_model">Device model</label>
                    <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg><input class="input" id="device_model" name="device_model" value="{{ old('device_model', $appUser?->device_model) }}" placeholder="e.g. Samsung Galaxy A54" maxlength="100" aria-invalid="{{ $invalid('device_model') }}"></div>
                    @error('device_model')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field span-2">
                    <label class="field-label" for="fcm_token">Push notification token (FCM)</label>
                    <textarea class="input" id="fcm_token" name="fcm_token" rows="2" maxlength="5000" placeholder="Paste the Firebase token if you have it" spellcheck="false" style="min-height:64px;font-family:ui-monospace,monospace;font-size:12px" aria-invalid="{{ $invalid('fcm_token') }}">{{ old('fcm_token', $appUser?->fcm_token) }}</textarea>
                    @error('fcm_token')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </details>

        <div class="form-actions">
            <span class="hint"><span class="req">*</span> Required fields</span>
            <a class="btn" href="{{ route('app-users.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit" data-loading-text="Saving…">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                <span data-label>{{ $editing ? 'Save changes' : 'Create user' }}</span>
            </button>
        </div>
    </div>

    <aside class="side-stack">
        <div class="form-card profile" aria-live="polite">
            <div class="profile-banner"></div>
            <div class="profile-main">
                <span class="big-avatar"><img src="{{ $photoUrl ?? '' }}" alt="" data-out="photo" @if(!$photoUrl) hidden @endif><span data-out="initial" @if($photoUrl) hidden @endif>{{ $initial }}</span></span>
                <strong data-out="name">{{ old('name', $appUser?->name) ?: 'New user' }}</strong>
                <span class="mail" data-out="email">{{ old('email', $appUser?->email) ?: 'email not set' }}</span>
                <span class="status-pill"><i class="dot {{ $status }}" data-out="dot"></i><span data-out="status">{{ ucfirst($status) }}</span></span>
            </div>
            <dl class="profile-rows">
                <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>User ID</dt><dd>@if($editing)<button type="button" class="id-copy" data-copy="{{ $appUser->id }}" title="Copy user ID">#{{ $appUser->id }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg></button>@else<span class="muted-id">Assigned after saving</span>@endif</dd></div>
                <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>Phone</dt><dd data-out="phone">{{ old('phone', $appUser?->phone) ?: '—' }}</dd></div>
                <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg>Platform</dt><dd data-out="platform">{{ $platform ?: '—' }}</dd></div>
                @if($editing)
                    <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>Joined</dt><dd title="{{ $appUser->created_at?->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}">{{ $appUser->created_at?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>Last login</dt><dd>{{ $appUser->last_login_at?->diffForHumans() ?? 'Never' }}</dd></div>
                    <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 8v4l2.5 2.5"/></svg>Activity</dt><dd><a href="{{ route('app-users.logs', $appUser) }}">{{ number_format($appUser->logs_count ?? 0) }} logs →</a></dd></div>
                @endif
            </dl>
        </div>
        <div class="form-card side-card tips-card">
            <h3>Good to know</h3>
            <ul class="tips">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/></svg>Passwords are hashed — nobody, including admins, can read them later.</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg><span><b>Blocked</b> users cannot sign in. <b>Inactive</b> is a temporary pause.</span></li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18h2"/></svg>Device details update automatically when the user signs in from the app.</li>
            </ul>
        </div>
    </aside>
</form>
@if($editing)
    <form id="start-trial-form" method="post" action="{{ route('app-users.start-trial', $appUser) }}" style="display:none">@csrf</form>
@endif
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const out = (key) => document.querySelector(`[data-out="${key}"]`);
    const name = document.getElementById('name'), email = document.getElementById('email');
    const updatePreview = () => {
        const n = name.value.trim();
        out('name').textContent = n || 'New user';
        out('initial').textContent = n ? n.charAt(0).toUpperCase() : '?';
        out('email').textContent = email.value.trim() || 'email not set';
        const checked = document.querySelector('input[name="status"]:checked');
        if (checked) {
            out('status').textContent = checked.value.charAt(0).toUpperCase() + checked.value.slice(1);
            out('dot').className = 'dot ' + checked.value;
        }
        out('phone').textContent = document.getElementById('phone').value.trim() || '—';
        const plat = document.querySelector('input[name="platform"]:checked');
        out('platform').textContent = plat && plat.value ? plat.value : '—';
    };
    document.getElementById('phone').addEventListener('input', updatePreview);
    document.querySelectorAll('[data-preview]').forEach((el) => el.addEventListener('input', updatePreview));
    document.querySelectorAll('input[name="status"], input[name="platform"]').forEach((el) => el.addEventListener('change', updatePreview));

    // Profile photo: pick / drag & drop, instant preview, client-side checks, remove + undo
    const photoInput = document.querySelector('[data-photo-input]');
    const photoField = document.querySelector('.photo-field');
    const drop = document.querySelector('[data-photo-drop]');
    const formImg = document.querySelector('[data-photo-img]');
    const formInitial = document.querySelector('[data-photo-initial]');
    const cardImg = out('photo');
    const removeBtn = document.querySelector('[data-photo-remove]');
    const undoBtn = document.querySelector('[data-photo-undo]');
    const removeFlag = document.querySelector('[data-photo-remove-flag]');
    const pickLabel = document.querySelector('[data-photo-pick-label]');
    const photoError = document.querySelector('[data-photo-error]');
    const original = formImg.hidden ? null : formImg.getAttribute('src');
    const allowedTypes = photoInput.accept.split(',');
    const maxBytes = Number(photoInput.dataset.maxBytes);
    let objectUrl = null;

    const showPhoto = (src) => {
        [formImg, cardImg].forEach((img) => { img.hidden = !src; if (src) img.src = src; });
        formInitial.hidden = !!src;
        out('initial').hidden = !!src;
        drop.classList.toggle('has-photo', !!src);
        pickLabel.textContent = src ? 'Change photo' : 'Upload photo';
    };
    const setError = (msg) => { photoError.textContent = msg; photoError.hidden = !msg; };
    const clearObjectUrl = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; };

    const usePhoto = (file) => {
        setError('');
        if (!file) return;
        if (!allowedTypes.includes(file.type)) { photoInput.value = ''; return setError('Please choose a ' + allowedTypes.map((t) => t.replace('image/', '').toUpperCase()).join(' or ') + ' image.'); }
        if (file.size > maxBytes) { photoInput.value = ''; return setError(`This photo is ${(file.size / 1048576).toFixed(1)} MB. The limit is ${Math.round(maxBytes / 1048576)} MB.`); }
        clearObjectUrl();
        objectUrl = URL.createObjectURL(file);
        removeFlag.value = '0';
        showPhoto(objectUrl);
        removeBtn.hidden = false;
        undoBtn.hidden = true;
        document.querySelector('[data-photo-reselect]')?.remove();
    };

    photoInput.addEventListener('change', () => usePhoto(photoInput.files[0]));
    drop.addEventListener('click', () => photoInput.click());
    drop.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); photoInput.click(); } });

    ['dragenter', 'dragover'].forEach((t) => photoField.addEventListener(t, (e) => { e.preventDefault(); photoField.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach((t) => photoField.addEventListener(t, (e) => { e.preventDefault(); photoField.classList.remove('drag'); }));
    photoField.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        photoInput.files = dt.files;
        usePhoto(file);
    });

    removeBtn.addEventListener('click', () => {
        photoInput.value = '';
        clearObjectUrl();
        setError('');
        removeFlag.value = original ? '1' : '0';
        showPhoto(null);
        removeBtn.hidden = true;
        undoBtn.hidden = !original;
    });
    undoBtn.addEventListener('click', () => {
        removeFlag.value = '0';
        showPhoto(original);
        removeBtn.hidden = false;
        undoBtn.hidden = true;
    });

    // Copy user ID
    document.querySelector('[data-copy]')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        try { await navigator.clipboard.writeText(btn.dataset.copy); } catch (err) { return; }
        btn.classList.add('copied'); btn.title = 'Copied!';
        setTimeout(() => { btn.classList.remove('copied'); btn.title = 'Copy user ID'; }, 1500);
    });

    // Password: reveal, generate, strength meter
    const password = document.getElementById('pin');
    const meter = document.querySelector('[data-meter]');
    const strength = document.querySelector('[data-strength]');
    const reveal = document.querySelector('[data-reveal]');
    const labels = ['Use exactly 6 numbers.', 'PIN must contain 6 numbers.', 'PIN format is valid.'];
    const score = (v) => {
        if (!v) return 0;
        let s = 0;
        if (v.length >= 8) s++;
        if (v.length >= 12) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
        return Math.max(1, Math.min(4, v.length < 8 ? 1 : s));
    };
    const updateMeter = () => {
        const s = /^\d{6}$/.test(password.value) ? 2 : (password.value.length ? 1 : 0);
        meter.dataset.score = String(s);
        if (strength) strength.textContent = labels[s];
    };
    const setReveal = (show) => {
        password.type = show ? 'text' : 'password';
        reveal.setAttribute('aria-pressed', String(show));
        reveal.title = show ? 'Hide password' : 'Show password';
    };
    password.addEventListener('input', updateMeter);
    reveal.addEventListener('click', () => setReveal(password.type === 'password'));
    document.querySelector('[data-generate]').addEventListener('click', () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*?';
        const bytes = new Uint32Array(14);
        crypto.getRandomValues(bytes);
        password.value = String(Math.floor(100000 + crypto.getRandomValues(new Uint32Array(1))[0] % 900000));
        setReveal(true);
        updateMeter();
        password.focus();
    });
    updateMeter();

    // Focus the first invalid field, otherwise the first field.
    (document.querySelector('[aria-invalid="true"]') || name).focus({ preventScroll: false });
})();
</script>
@endpush
