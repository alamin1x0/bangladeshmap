@php
    $down = $web['site_status'] === 'down';
    $logoUrl = \App\Support\WebSettings::logoUrl();
    $until = null;
    if (! $down && $web['maintenance_until'] !== '') {
        try { $until = \Illuminate\Support\Carbon::parse($web['maintenance_until'], 'Asia/Dhaka'); } catch (\Throwable) { $until = null; }
        if ($until && $until->isPast()) { $until = null; }
    }
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $web['theme_color'] }}">
    <title>{{ $down ? 'সাময়িকভাবে বন্ধ' : 'রক্ষণাবেক্ষণ চলছে' }} · {{ $web['site_name'] }}</title>
@include('partials.brand-head')
    <style>
        :root{--c:{{ $web['theme_color'] }};--ink:#17231d;--muted:#5d6b63}
        *{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:grid;place-items:center;padding:24px 16px;font-family:"Hind Siliguri","Noto Sans Bengali","Nirmala UI",system-ui,sans-serif;color:var(--ink);background:radial-gradient(800px 400px at 80% 0,color-mix(in srgb,var(--c) 22%,transparent),transparent),#f7f8f4;line-height:1.6}
        .card{width:min(520px,100%);background:#fff;border-radius:24px;padding:34px 28px;text-align:center;box-shadow:0 30px 70px -40px #0f2a1e88}
        .logo{display:flex;justify-content:center;align-items:center;gap:10px;font-weight:800;font-size:17px;margin-bottom:26px}
        .logo img.ic{width:38px;height:38px;border-radius:11px}
        .logo img.lg{max-height:40px;max-width:200px;object-fit:contain}
        .art{width:84px;height:84px;margin:0 auto 18px;border-radius:26px;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c)}
        .art svg{width:42px;height:42px}
        .art.down{background:#fdecec;color:#c62828}
        h1{font-size:24px;line-height:1.35;margin-bottom:8px}
        p{color:var(--muted);font-size:16px}
        .count{display:flex;justify-content:center;gap:10px;margin:22px 0 4px}
        .count div{min-width:66px;padding:10px 6px;border-radius:14px;background:color-mix(in srgb,var(--c) 8%,#fff)}
        .count b{display:block;font-size:24px;color:var(--c);font-variant-numeric:tabular-nums;line-height:1.2}
        .count span{font-size:12px;color:var(--muted)}
        .back{font-size:13.5px;color:var(--muted);margin-top:8px}
        .contact{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:24px}
        .contact a{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;border-radius:999px;background:var(--c);color:#fff;text-decoration:none;font-weight:700;font-size:14.5px}
        .contact a.alt{background:color-mix(in srgb,var(--c) 10%,#fff);color:var(--c)}
        .bar{height:4px;border-radius:9px;background:color-mix(in srgb,var(--c) 12%,#fff);overflow:hidden;margin:24px auto 0;width:60%}
        .bar i{display:block;height:100%;width:40%;border-radius:9px;background:var(--c);animation:slide 1.6s ease-in-out infinite}
        @keyframes slide{0%{transform:translateX(-100%)}100%{transform:translateX(250%)}}
        @media (prefers-reduced-motion:reduce){.bar i{animation:none}}
    </style>
</head>
<body>
<main class="card">
    <div class="logo">@if($logoUrl)<img class="lg" src="{{ $logoUrl }}" alt="{{ $web['site_name'] }}">@else<img class="ic" src="{{ \App\Support\WebSettings::icons()['192'] }}" alt="">{{ $web['site_name'] }}@endif</div>
    @if($down)
        <div class="art down"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18.4 5.6A9 9 0 1 1 5.6 18.4 9 9 0 0 1 18.4 5.6zM5.6 5.6l12.8 12.8"/></svg></div>
        <h1>সাময়িকভাবে বন্ধ</h1>
        <p>{{ $web['down_message'] }}</p>
    @else
        <div class="art"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg></div>
        <h1>{{ $web['maintenance_title'] }}</h1>
        <p>{{ $web['maintenance_message'] }}</p>
        @if($until)
            <div class="count" id="count" data-until="{{ $until->timestamp }}" aria-live="polite">
                <div><b data-k="h">--</b><span>ঘণ্টা</span></div><div><b data-k="m">--</b><span>মিনিট</span></div><div><b data-k="s">--</b><span>সেকেন্ড</span></div>
            </div>
            <p class="back">আনুমানিক ফিরে আসবে: {{ $until->format('d M, h:i A') }}</p>
        @else
            <div class="bar" aria-hidden="true"><i></i></div>
        @endif
    @endif
    @if($phone || $web['whatsapp'] !== '' || $web['support_email'] !== '')
        <div class="contact">
            @if($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">📞 {{ $phone }}</a>@endif
            @if($web['whatsapp'] !== '')<a class="alt" href="https://wa.me/{{ ltrim($web['whatsapp'], '+') }}" target="_blank" rel="noopener">💬 WhatsApp</a>@endif
            @if($web['support_email'] !== '')<a class="alt" href="mailto:{{ $web['support_email'] }}">✉️ ইমেইল</a>@endif
        </div>
    @endif
</main>
@if($until)
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const el = document.getElementById('count'), end = +el.dataset.until * 1000;
    const bn = n => String(n).padStart(2, '0').replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
    const tick = () => {
        let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        if (s === 0) { location.reload(); return; }
        el.querySelector('[data-k=h]').textContent = bn(Math.floor(s / 3600));
        el.querySelector('[data-k=m]').textContent = bn(Math.floor(s % 3600 / 60));
        el.querySelector('[data-k=s]').textContent = bn(s % 60);
        setTimeout(tick, 1000);
    };
    tick();
})();
</script>
@endif
</body>
</html>
