<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ \App\Support\WebSettings::get('site_name') }}</title>
    <meta name="robots" content="noindex, nofollow">
@include('partials.brand-head')
    <script nonce="{{ Vite::cspNonce() }}">if (localStorage.getItem('geonexa-theme') === 'dark') document.documentElement.dataset.theme = 'dark';</script>
    <style>
        :root { --ink:#172033; --muted:#758198; --primary:#2563eb; --bg:#f4f7fc; --surface:#fff; --line:#dfe6f0; }
        [data-theme="dark"] { --ink:#edf3ff; --muted:#91a0b9; --primary:#73a3ff; --bg:#0d1425; --surface:#131e34; --line:#293752; }
        *{box-sizing:border-box} body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 85% 15%,#4e8dff55,transparent 30%),linear-gradient(135deg,#102044,#2563eb);font:14px/1.5 Inter,system-ui,sans-serif;color:var(--ink)}.wrap{width:min(430px,calc(100% - 32px));position:relative}.box{background:var(--surface);border:1px solid #ffffff25;border-radius:20px;padding:40px;box-shadow:0 25px 70px #08153466}.theme{position:absolute;right:14px;top:-50px;width:38px;height:38px;border:1px solid #ffffff44;border-radius:50%;background:#ffffff18;color:#fff;cursor:pointer}.theme svg{width:17px;height:17px}.logo{display:flex;align-items:center;gap:10px;font-size:21px;font-weight:800;margin-bottom:35px}.mark{display:grid;place-items:center;width:37px;height:37px;background:linear-gradient(135deg,#62a0ff,#2563eb);color:#fff;border-radius:11px;box-shadow:0 7px 17px #2563eb55}.eyebrow{color:var(--primary);font-weight:700;font-size:11px;letter-spacing:1px;text-transform:uppercase;margin:0 0 6px}h1{font-size:27px;letter-spacing:-.7px;margin:0 0 7px}.intro{color:var(--muted);margin:0 0 26px}.field{display:block;color:var(--ink);font-weight:600;margin:17px 0 7px}input{width:100%;padding:12px 13px;border:1px solid var(--line);border-radius:9px;background:transparent;color:var(--ink);font:inherit;outline:none}input:focus{border-color:var(--primary);box-shadow:0 0 0 3px #2563eb18}.btn{width:100%;margin-top:25px;padding:13px;border:0;border-radius:9px;background:var(--primary);color:#fff;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 8px 18px #2563eb38}.btn:hover{filter:brightness(1.08)}.error{color:#e05252;font-size:12px;margin-top:6px}
    </style>
</head>
<body><div class="wrap"><button class="theme" id="theme" aria-label="Toggle dark mode"></button><div class="box">@php $__logo = \App\Support\WebSettings::logoUrl(); @endphp<div class="logo">@if($__logo)<img src="{{ $__logo }}" alt="{{ \App\Support\WebSettings::get('site_name') }}" style="max-height:42px;max-width:220px;object-fit:contain">@else<img class="mark" src="{{ \App\Support\WebSettings::icons()['192'] }}" alt="" width="37" height="37">{{ \App\Support\WebSettings::get('site_name') }}@endif</div><p class="eyebrow">Admin portal</p><h1>Welcome back</h1><p class="intro">Sign in to manage your workspace securely.</p>
    <form method="post" action="{{ route('login.store') }}">@csrf
        <label class="field" for="username">Username</label><input id="username" name="username" value="{{ old('username') }}" autocomplete="username" placeholder="Enter your username" required>
        @error('username')<div class="error">{{ $message }}</div>@enderror
        <label class="field" for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
        @error('password')<div class="error">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Sign in to dashboard</button>
    </form>
</div></div>
<script nonce="{{ Vite::cspNonce() }}">
    const root=document.documentElement, button=document.getElementById('theme');
    const icon=()=>button.innerHTML=root.dataset.theme==='dark'?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2"/></svg>':'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8Z"/></svg>';
    button.onclick=()=>{root.dataset.theme=root.dataset.theme==='dark'?'light':'dark';localStorage.setItem('geonexa-theme',root.dataset.theme);icon()};icon();
</script>
</body></html>
