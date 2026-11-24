@php
    $bn = fn ($n) => strtr((string) $n, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    $priceBn = $bn(number_format($price));
    $orderId = session('order_success');
    $err = $errors->getBag('order');
    $tokens = ['{price}' => $priceBn, '{divisions}' => $bn($totals['divisions']), '{districts}' => $bn($totals['districts']), '{upazilas}' => $bn($totals['upazilas']), '{bkash}' => (string) $bkash, '{phone}' => (string) $phone];
    $t = fn (string $key) => trim(strtr($text[$key] ?? '', $tokens));
    $h = fn (string $key) => nl2br(e($t($key)), false);
    $parts = fn (string $key) => array_pad(array_map('trim', explode('|', $t($key), 3)), 3, '');
    $palette = ['#2f6b4f', '#3c7d8c', '#8a6d2f', '#5b6fb0', '#a0543d', '#4f7d3a', '#7a4f8c', '#2f6f73'];
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cut">
    <title>{{ $t('seo.title') }}</title>
    <meta name="description" content="{{ $t('seo.description') }}">
    <meta name="theme-color" content="{{ $web['theme_color'] }}">
@if(! $web['allow_indexing'])
    <meta name="robots" content="noindex, nofollow">
@endif
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $t('seo.title') }}">
    <meta property="og:description" content="{{ $t('seo.description') }}">
    <link rel="canonical" href="{{ route('home') }}">
    <meta property="og:image" content="{{ $logoUrl ?: \App\Support\WebSettings::icons()['512'] }}">
@include('partials.brand-head')
    <style>
        :root{--g9:#123526;--g8:#1f4d3a;--g7:#2a6049;--g1:#e8f0eb;--y:#f4c34d;--y2:#e9b232;--cream:#faf8f2;--ink:#17231d;--muted:#5d6b63;--line:#e3e6df;--card:#fff;--r:16px;
            --font:"Hind Siliguri","Noto Sans Bengali","Kohinoor Bangla","Nirmala UI","Vrinda",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
        *{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth;scroll-padding-top:72px;-webkit-text-size-adjust:100%}
        body{font-family:var(--font);background:var(--cream);color:var(--ink);line-height:1.65;font-size:16px;-webkit-font-smoothing:antialiased;overflow-x:hidden}
        a{color:inherit}
        img,svg{display:block}
        .wrap{width:min(1140px,100% - 32px);margin-inline:auto}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 22px;border-radius:999px;font:inherit;font-weight:700;font-size:15px;text-decoration:none;border:0;cursor:pointer;transition:transform .15s,box-shadow .15s,background .15s;white-space:nowrap}
        .btn:hover{transform:translateY(-1px)}
        .btn-y{background:var(--y);color:var(--g9);box-shadow:0 8px 22px -10px #c28d0f}
        .btn-y:hover{background:var(--y2)}
        .btn-ghost{border:1.5px solid #ffffff55;color:#fff;background:#ffffff0d}
        .btn-ghost:hover{background:#ffffff1f}
        .btn-g{background:var(--g8);color:#fff}
        .btn-g:hover{background:var(--g9)}
        .btn svg{width:17px;height:17px}
        .eyebrow{display:inline-block;font-size:13px;font-weight:700;color:var(--g7);letter-spacing:.3px;margin-bottom:8px}
        h2{font-size:clamp(26px,3.6vw,38px);line-height:1.25;letter-spacing:-.3px;color:var(--g9)}
        .lead{color:var(--muted);font-size:16.5px;margin-top:10px;max-width:640px}
        section{padding:84px 0}
        .center{text-align:center}.center .lead{margin-inline:auto}
        :focus-visible{outline:3px solid var(--y);outline-offset:2px}

        /* Nav */
        .nav{position:sticky;top:0;z-index:50;background:#1f4d3aee;backdrop-filter:saturate(1.4) blur(10px);-webkit-backdrop-filter:saturate(1.4) blur(10px);border-bottom:1px solid #ffffff14}
        .nav .wrap{display:flex;align-items:center;gap:18px;height:62px}
        .brand{display:flex;align-items:center;gap:9px;color:#fff;text-decoration:none;font-weight:700;font-size:15px}
        .brand i{display:grid;place-items:center;width:32px;height:32px;border-radius:9px;background:var(--y);color:var(--g9);font-style:normal;font-weight:800;font-size:14px}
        .nav-links{display:flex;gap:4px;margin-left:auto}
        .nav-links a{color:#dfeae3;text-decoration:none;font-size:14.5px;padding:7px 12px;border-radius:999px}
        .nav-links a:hover{background:#ffffff14;color:#fff}
        .nav .btn{padding:9px 18px;font-size:14px}
        .burger{display:none;margin-left:auto;width:40px;height:40px;border-radius:10px;border:1px solid #ffffff2a;background:transparent;color:#fff;cursor:pointer}
        .burger svg{width:20px;height:20px;margin:auto}

        /* Hero */
        .hero{position:relative;background:radial-gradient(1200px 500px at 85% 10%,#2f6b52 0,transparent 60%),linear-gradient(160deg,var(--g8),var(--g9));color:#fff;padding:72px 0 0;overflow:hidden}
        .hero::before{content:"";position:absolute;inset:0;background-image:radial-gradient(#ffffff12 1px,transparent 1px);background-size:22px 22px;mask-image:linear-gradient(180deg,#000,transparent 85%);-webkit-mask-image:linear-gradient(180deg,#000,transparent 85%)}
        .hero .wrap{position:relative;display:grid;grid-template-columns:1.1fr .9fr;gap:40px;align-items:center}
        .pill{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:999px;background:#ffffff14;border:1px solid #ffffff22;font-size:13px;color:#e6efe9}
        .pill b{width:7px;height:7px;border-radius:50%;background:var(--y);box-shadow:0 0 0 4px #f4c34d33}
        .hero h1{font-size:clamp(38px,6vw,64px);line-height:1.05;letter-spacing:-1.2px;margin:18px 0 10px;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-weight:800}
        .hero h1 span{color:var(--y)}
        .hero .bn-title{font-size:clamp(20px,2.6vw,26px);font-weight:700;color:var(--y)}
        .hero p.desc{color:#d3e2d9;font-size:17px;margin-top:10px;max-width:540px}
        .hero-cta{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px}
        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:38px;max-width:520px}
        .stats div{border-left:2px solid #ffffff26;padding-left:12px}
        .stats strong{display:block;font-size:30px;line-height:1.1;font-weight:800}
        .stats span{font-size:13px;color:#b9cfc2}

        /* Book mockup */
        .book-stage{position:relative;height:440px;perspective:1400px}
        .sheet,.cover{position:absolute;border-radius:6px}
        .sheet{width:260px;height:340px;background:#fff;box-shadow:0 30px 60px -25px #000a;padding:22px 20px}
        .sheet.s1{left:52%;top:70px;transform:rotate(9deg)}
        .sheet.s2{left:6%;top:96px;transform:rotate(-8deg);background:#fdfdfb}
        .sheet .ln{height:5px;border-radius:3px;background:#e4e8e3;margin:9px 0}
        .sheet .ln.s{width:60%}.sheet .ln.m{width:80%}
        .sheet h5{font-size:15px;color:var(--g8);margin-bottom:12px;font-family:system-ui,sans-serif}
        .sheet .map{height:150px;border-radius:6px;background:var(--g1);margin-top:12px;overflow:hidden}
        .cover{left:22%;top:20px;width:290px;height:380px;background:linear-gradient(150deg,#2c6b50,#1a4332);color:#fff;padding:30px 26px;transform:rotate(-2deg) rotateY(-8deg);box-shadow:0 40px 80px -30px #000c,inset 6px 0 0 #ffffff14;font-family:system-ui,sans-serif;display:flex;flex-direction:column}
        .cover small{font-size:10px;letter-spacing:2px;color:#cfe2d7;text-transform:uppercase}
        .cover h4{font-size:29px;line-height:1.08;margin:12px 0 8px}
        .cover h4 span{color:var(--y)}
        .cover p{font-size:12px;color:#cfe2d7;font-family:var(--font)}
        .cover .bottom{margin-top:auto;display:flex;justify-content:space-between;align-items:end}
        .cover .bottom b{font-size:13px}
        .cover .badge{background:var(--y);color:var(--g9);font-size:12px;font-weight:800;padding:6px 10px;border-radius:8px;font-family:var(--font)}
        .float-chip{position:absolute;right:2%;bottom:46px;background:#fff;color:var(--ink);border-radius:14px;padding:10px 14px;display:flex;gap:10px;align-items:center;box-shadow:0 20px 40px -18px #000b;font-size:13px}
        .float-chip i{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:var(--g1);color:var(--g8);font-style:normal}
        .float-chip i svg{width:18px;height:18px}
        .float-chip b{display:block;font-size:14px}

        .strip{position:relative;background:#fff;border-bottom:1px solid var(--line);margin-top:56px}
        .strip .wrap{display:flex;justify-content:center;gap:12px 34px;flex-wrap:wrap;padding:16px 0;font-size:14.5px;color:var(--muted)}
        .strip b{color:var(--g9)}
        .strip span{display:flex;align-items:center;gap:8px}
        .strip span::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--y2)}

        /* Features */
        .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:38px}
        .feat{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:22px;transition:transform .2s,box-shadow .2s,border-color .2s}
        .feat:hover{transform:translateY(-3px);box-shadow:0 18px 40px -26px #1f4d3a66;border-color:#cfd9d2}
        .feat .ic{display:grid;place-items:center;width:44px;height:44px;border-radius:12px;background:var(--g1);color:var(--g8);margin-bottom:14px}
        .feat .ic svg{width:22px;height:22px}
        .feat h3{font-size:17.5px;color:var(--g9);margin-bottom:4px}
        .feat p{font-size:14.5px;color:var(--muted)}

        /* Preview */
        .preview{background:#fff;border-block:1px solid var(--line)}
        .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:38px}
        .div-card{border:1px solid var(--line);border-radius:var(--r);overflow:hidden;background:var(--cream);display:flex;flex-direction:column}
        .div-card .art{position:relative;aspect-ratio:4/3;background:#fff;border-bottom:1px solid var(--line);overflow:hidden}
        .div-card .art svg{width:100%;height:100%}
        .div-card .art .tag{position:absolute;left:10px;top:10px;background:#ffffffe6;border:1px solid var(--line);border-radius:999px;font-size:11.5px;padding:2px 9px;color:var(--muted);font-family:system-ui,sans-serif}
        .div-card .body{padding:14px 15px 15px;display:flex;flex-direction:column;gap:6px;flex:1}
        .div-card h3{font-size:17px;color:var(--g9);line-height:1.3}
        .div-card h3 small{font-size:12px;color:var(--muted);font-weight:500;font-family:system-ui,sans-serif;margin-left:4px}
        .div-card .meta{font-size:13.5px;color:var(--muted)}
        .div-card details{margin-top:auto;font-size:13.5px}
        .div-card summary{cursor:pointer;color:var(--g7);font-weight:700;list-style:none;display:inline-flex;align-items:center;gap:4px}
        .div-card summary::-webkit-details-marker{display:none}
        .div-card summary::after{content:"＋";font-size:12px}
        .div-card details[open] summary::after{content:"－"}
        .div-card .dlist{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
        .div-card .dlist span{background:#fff;border:1px solid var(--line);border-radius:999px;padding:1px 9px;font-size:12.5px}

        /* Two clicks */
        .split{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center}
        .pages{position:relative;height:330px}
        .pg{position:absolute;width:44%;aspect-ratio:3/4;background:#fff;border-radius:8px;box-shadow:0 24px 50px -28px #1f4d3a88;border:1px solid var(--line);padding:14px}
        .pg.p1{left:0;top:20px;background:linear-gradient(150deg,#2c6b50,#1a4332);color:#fff;font-family:system-ui,sans-serif;font-weight:800;font-size:17px;line-height:1.1;padding:18px;z-index:2}
        .pg.p1 span{color:var(--y)}
        .pg.p2{left:30%;top:0;z-index:3}
        .pg.p3{left:58%;top:34px;z-index:1}
        .pg .row{display:flex;justify-content:space-between;gap:6px;font-size:10.5px;color:var(--muted);border-bottom:1px dashed var(--line);padding:3.5px 0}
        .pg .row b{color:var(--g8);font-weight:600}
        .pg h6{font-size:12px;color:var(--g8);margin-bottom:6px}
        .checks{list-style:none;margin-top:24px;display:grid;gap:4px}
        .checks li{display:flex;gap:12px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--line);font-size:15.5px}
        .checks li i{flex:0 0 24px;height:24px;border-radius:50%;background:var(--g8);color:#fff;display:grid;place-items:center;margin-top:1px}
        .checks li i svg{width:13px;height:13px}

        /* Audience */
        .aud{background:var(--g1)}
        .grid-aud{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:34px}
        .aud-card{background:#ffffffc7;border:1px solid #d6e3da;border-radius:14px;padding:16px}
        .aud-card h3{font-size:16px;color:var(--g9);display:flex;align-items:center;gap:8px}
        .aud-card h3 span{font-size:19px}
        .aud-card p{font-size:13.5px;color:var(--muted);margin-top:4px}

        /* Pricing */
        .price-grid{display:grid;grid-template-columns:1fr 1.05fr;gap:22px;margin-top:40px;align-items:stretch}
        .price-card{position:relative;background:linear-gradient(160deg,var(--g8),var(--g9));color:#fff;border-radius:22px;padding:30px;box-shadow:0 30px 60px -32px #123526;overflow:hidden}
        .price-card::after{content:"";position:absolute;right:-60px;top:-60px;width:200px;height:200px;border-radius:50%;background:#f4c34d1f}
        .price-card .ribbon{position:absolute;right:18px;top:18px;background:var(--y);color:var(--g9);font-size:12px;font-weight:800;padding:4px 11px;border-radius:999px}
        .price-card h3{font-size:20px;font-family:system-ui,sans-serif;padding-right:110px}
        .price-card .sub{color:#c9dbd0;font-size:14px}
        .price-card .amount{font-size:58px;font-weight:800;line-height:1;margin:22px 0 4px;display:flex;align-items:flex-start;gap:4px}
        .price-card .amount small{font-size:26px;margin-top:6px}
        .price-card .once{color:#c9dbd0;font-size:14px}
        .price-card ul{list-style:none;margin:22px 0 26px;display:grid;gap:9px}
        .price-card li{display:flex;gap:10px;font-size:15px}
        .price-card li::before{content:"✓";color:var(--y);font-weight:800}
        .price-card .btn{width:100%}
        .table-card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:26px}
        .table-card h3{font-size:20px;color:var(--g9)}
        .table-card .sub{color:var(--muted);font-size:14px}
        .tbl{width:100%;border-collapse:collapse;margin-top:16px;font-size:14.5px}
        .tbl th{font-size:12.5px;color:var(--muted);font-weight:600;text-align:right;padding:8px 6px;border-bottom:1px solid var(--line)}
        .tbl th:first-child,.tbl td:first-child{text-align:left}
        .tbl td{padding:9px 6px;border-bottom:1px solid #f0f2ee;text-align:right;font-variant-numeric:tabular-nums}
        .tbl tfoot td{font-weight:800;color:var(--g9);border-bottom:0;border-top:2px solid var(--line)}

        /* Order */
        .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:36px}
        .step{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:20px}
        .step b{display:grid;place-items:center;width:36px;height:36px;border-radius:11px;background:var(--y);color:var(--g9);font-size:17px;margin-bottom:12px}
        .step h3{font-size:17px;color:var(--g9)}
        .step p{font-size:14.5px;color:var(--muted)}
        .step .num{display:inline-block;margin-top:6px;font-weight:800;color:var(--g8);font-size:15px;letter-spacing:.5px;font-family:system-ui,sans-serif}
        .order-box{display:grid;grid-template-columns:1.4fr .8fr;gap:18px;margin-top:18px}
        .form-card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:26px}
        .fgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .fgrid .full{grid-column:1/-1}
        label{display:block;font-size:14px;font-weight:600;margin-bottom:6px;color:var(--g9)}
        label em{color:#b42318;font-style:normal}
        .in{width:100%;font:inherit;font-size:16px;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:var(--cream);color:var(--ink);transition:border-color .15s,box-shadow .15s}
        .in:focus{outline:none;border-color:var(--g7);background:#fff;box-shadow:0 0 0 4px #2a604922}
        .in.bad{border-color:#d92d20;background:#fff8f7}
        textarea.in{min-height:96px;resize:vertical}
        .ferr{color:#b42318;font-size:13px;margin-top:5px}
        .hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
        .form-foot{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:18px}
        .form-foot small{color:var(--muted);font-size:13px}
        .alert{display:flex;gap:10px;align-items:flex-start;border-radius:12px;padding:12px 14px;font-size:14.5px;margin-bottom:16px}
        .alert.bad{background:#fdecec;color:#912018}
        .success{text-align:center;padding:26px 10px}
        .success .tick{width:64px;height:64px;border-radius:50%;background:#e3f4e8;color:#12733b;display:grid;place-items:center;margin:0 auto 14px}
        .success .tick svg{width:32px;height:32px}
        .success h3{font-size:22px;color:var(--g9)}
        .success p{color:var(--muted);margin-top:6px}
        .success .oid{display:inline-block;margin-top:12px;background:var(--g1);color:var(--g8);font-weight:800;padding:6px 14px;border-radius:999px;font-family:system-ui,sans-serif}
        .side{display:grid;gap:14px;align-content:start}
        .contact{background:var(--g8);color:#fff;border-radius:22px;padding:22px}
        .contact small{color:#c9dbd0;font-size:13px}
        .contact a.tel{display:block;font-size:24px;font-weight:800;text-decoration:none;margin:4px 0 14px;font-family:system-ui,sans-serif;letter-spacing:.3px}
        .contact .btn{width:100%}
        .assure{background:#fff;border:1px solid var(--line);border-radius:22px;padding:20px;display:grid;gap:12px;font-size:14px;color:var(--muted)}
        .assure div{display:flex;gap:10px}
        .assure b{color:var(--g9);display:block;font-size:14.5px}
        .assure i{flex:0 0 30px;height:30px;border-radius:9px;background:var(--g1);color:var(--g8);display:grid;place-items:center;font-style:normal}

        /* FAQ */
        .faq{max-width:820px;margin:34px auto 0;display:grid;gap:10px}
        .faq details{background:#fff;border:1px solid var(--line);border-radius:14px;padding:0 18px}
        .faq summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:14px;align-items:center;padding:16px 0;font-weight:700;color:var(--g9);font-size:16px}
        .faq summary::-webkit-details-marker{display:none}
        .faq summary::after{content:"";flex:0 0 22px;height:22px;border-radius:50%;background:var(--g1) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231f4d3a' stroke-width='2.5' stroke-linecap='round'%3E%3Cpath d='M12 6v12M6 12h12'/%3E%3C/svg%3E") center/13px no-repeat;transition:transform .2s}
        .faq details[open] summary::after{transform:rotate(45deg)}
        .faq details p{padding:0 0 16px;color:var(--muted);font-size:15px}

        /* CTA + footer */
        .cta{padding:0 0 84px}
        .cta-box{position:relative;overflow:hidden;background:radial-gradient(600px 240px at 80% 0,#2f6b52,transparent),linear-gradient(160deg,var(--g8),var(--g9));color:#fff;border-radius:28px;padding:56px 24px;text-align:center}
        .cta-box h2{color:#fff}
        .cta-box p{color:#c9dbd0;margin:10px 0 24px}
        footer{border-top:1px solid var(--line);padding:26px 0 34px;font-size:13.5px;color:var(--muted)}
        footer .wrap{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
        footer a{text-decoration:none}
        footer a:hover{color:var(--g8)}
        .mbar{display:none}

        @media (max-width:960px){
            section{padding:64px 0}
            .hero .wrap,.split,.price-grid,.order-box{grid-template-columns:1fr}
            .book-stage{height:380px;max-width:480px;margin:0 auto;width:100%}
            .grid3{grid-template-columns:1fr 1fr}
            .grid4,.grid-aud{grid-template-columns:1fr 1fr}
            .pages{max-width:520px;margin:0 auto;width:100%}
        }
        @media (max-width:760px){
            .nav-links{display:none;position:absolute;left:0;right:0;top:62px;flex-direction:column;background:var(--g8);padding:8px 16px 16px;border-bottom:1px solid #ffffff1a}
            .nav.open .nav-links{display:flex}
            .nav-links a{padding:11px 12px;font-size:16px}
            .burger{display:grid}
            .nav .btn{display:none}
        }
        @media (max-width:640px){
            body{font-size:15px}
            section{padding:52px 0}
            .hero{padding-top:40px}
            .stats{grid-template-columns:repeat(2,1fr);gap:14px}
            .stats strong{font-size:26px}
            .book-stage{height:330px;margin-top:8px}
            .cover{left:50%;margin-left:-118px;width:236px;height:310px;padding:24px 20px}
            .cover h4{font-size:24px}
            .sheet{width:190px;height:250px;padding:16px}
            .sheet.s1{left:auto;right:-6px;top:50px}
            .sheet.s2{left:-6px;top:64px}
            .float-chip{display:none}
            .feat{display:grid;grid-template-columns:44px 1fr;gap:0 14px;padding:16px}
            .feat .ic{grid-row:span 2;margin:0}
            .grid3,.steps,.fgrid{grid-template-columns:1fr}
            .grid4{grid-template-columns:1fr 1fr;gap:10px}
            .div-card .body{padding:11px}
            .div-card h3{font-size:15.5px}
            .div-card h3 small{display:block;margin:0}
            .grid-aud{grid-template-columns:1fr 1fr;gap:10px}
            .aud-card{padding:13px}
            .pages{height:250px}
            .price-card,.table-card,.form-card{padding:20px}
            .price-card .amount{font-size:50px}
            .strip .wrap{justify-content:flex-start;gap:8px 18px;font-size:13.5px}
            .cta-box{padding:40px 18px;border-radius:22px}
            .mbar{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:60;gap:10px;align-items:center;justify-content:space-between;padding:10px 16px calc(10px + env(safe-area-inset-bottom));background:#fffffff2;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-top:1px solid var(--line);transform:translateY(110%);transition:transform .25s}
            .mbar.show{transform:none}
            .mbar strong{font-size:20px;color:var(--g9);line-height:1.1;display:block}
            .mbar small{color:var(--muted);font-size:12px}
            footer{padding-bottom:96px}
        }
        /* Mobile app section */
        .app-sec{padding:0 0 84px}
        .app-box{position:relative;overflow:hidden;display:grid;grid-template-columns:1.2fr .8fr;gap:30px;align-items:center;background:radial-gradient(500px 260px at 90% 10%,#2f6b52,transparent),linear-gradient(150deg,var(--g8),var(--g9));color:#fff;border-radius:28px;padding:48px 48px 0}
        .app-txt{padding-bottom:48px}
        .app-name{display:flex;align-items:center;gap:9px;flex-wrap:wrap;font-size:15px}
        .app-name img{border-radius:10px}
        .app-name em{font-style:normal;font-size:12.5px;padding:2px 10px;border-radius:99px;background:#ffffff1f;color:#f4dfa0}
        .app-txt h2{color:#fff;margin:14px 0 8px}
        .app-txt p{color:#c9dbd0;font-size:16.5px;max-width:520px;margin-bottom:24px}
        .stores{display:flex;gap:10px;flex-wrap:wrap}
        .store{display:inline-flex;align-items:center;gap:10px;padding:9px 18px 9px 13px;border-radius:12px;background:#000;color:#fff;text-decoration:none;border:1px solid #ffffff3a;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;font-weight:700;font-size:18px;line-height:1.05;transition:transform .15s,box-shadow .15s}
        .store:hover{transform:translateY(-2px);box-shadow:0 12px 24px -12px #000}
        .store svg{width:26px;height:26px;flex:0 0 26px}
        .store small{display:block;font-size:10.5px;font-weight:500;letter-spacing:.2px;opacity:.85}
        .stores.sm .store{padding:6px 12px 6px 9px;font-size:13.5px;border-radius:9px;border-color:#0000}
        .stores.sm .store svg{width:18px;height:18px;flex-basis:18px}
        .stores.sm .store small{font-size:8.5px}
        .phone{justify-self:center;width:240px;height:370px;border-radius:34px 34px 0 0;background:#0d1512;padding:10px 10px 0;box-shadow:0 -10px 40px -10px #0008;align-self:end}
        .screen{height:100%;border-radius:26px 26px 0 0;background:#f6f7f3;color:var(--ink);overflow:hidden;padding:0 12px}
        .sbar{display:flex;justify-content:space-between;align-items:center;font-size:11px;font-weight:700;padding:8px 6px 6px;font-family:system-ui,sans-serif}
        .sbar i{width:56px;height:16px;border-radius:99px;background:#0d1512;position:relative;left:-58px}
        .ahead{display:flex;align-items:center;gap:7px;font-size:14px;margin:4px 0 9px}
        .ahead img{border-radius:6px}
        .asearch{background:#fff;border:1px solid var(--line);border-radius:10px;padding:6px 10px;font-size:12px;color:var(--muted);margin-bottom:9px}
        .amap{background:#fff;border-radius:12px;border:1px solid var(--line);margin-bottom:8px}
        .amap svg{width:100%;height:auto}
        .arow{display:flex;justify-content:space-between;align-items:center;background:#fff;border-radius:9px;padding:6px 10px;margin-bottom:5px;font-size:12.5px;font-weight:600}
        .arow small{color:var(--muted);font-weight:500;font-size:11px}
        .f-stores{display:flex;justify-content:center}
        @media (max-width:960px){.app-box{grid-template-columns:1fr;padding:36px 28px 0;text-align:left}.app-txt{padding-bottom:0}}
        @media (max-width:640px){.app-sec{padding-bottom:52px}.app-box{border-radius:22px;padding:28px 20px 0;gap:22px}.store{font-size:16px;padding:8px 14px 8px 11px}.phone{width:210px;height:300px}}
        /* Web settings extras */
        .brand-ic{width:32px;height:32px;border-radius:9px}
        .brand-logo{max-height:34px;max-width:170px;width:auto;object-fit:contain;display:block}
        .announce{background:var(--y);color:var(--g9);text-align:center;font-size:14px;font-weight:700;padding:8px 16px}
        .announce a{text-decoration:none}.announce a:hover{text-decoration:underline}
        .admin-banner{background:#7a1f1f;color:#fff;text-align:center;font-size:13.5px;padding:7px 16px;font-family:system-ui,sans-serif}
        .admin-banner a{color:#ffd7d7;margin-left:6px}
        .price-grid.single{grid-template-columns:minmax(0,520px);justify-content:center}
        .wa-float{position:fixed;right:18px;bottom:22px;z-index:55;width:54px;height:54px;border-radius:50%;background:#25d366;color:#fff;display:grid;place-items:center;box-shadow:0 12px 26px -10px #128c7e;transition:transform .15s}
        .wa-float:hover{transform:scale(1.06)}
        .wa-float svg{width:28px;height:28px}
        footer .wrap{align-items:center}
        .f-brand{display:flex;flex-direction:column;gap:2px}
        .f-brand b{color:var(--g9);font-size:15px}
        .f-contact{display:flex;flex-wrap:wrap;gap:6px 16px;justify-content:center}
        .f-contact span{color:var(--muted)}
        .f-admin{opacity:.7}
        @media (max-width:640px){footer .wrap{flex-direction:column;text-align:center}.f-brand{align-items:center}.wa-float{bottom:84px;right:14px;width:50px;height:50px}}
        @media (prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
    </style>
    <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">
        {!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'Product',
            'name' => 'Bangladesh: Every Upazila Unveiled', 'inLanguage' => 'bn',
            'description' => "{$totals['divisions']} divisions, {$totals['districts']} districts and {$totals['upazilas']} upazilas of Bangladesh in one map book.",
            'offers' => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => 'BDT', 'availability' => 'https://schema.org/InStock', 'url' => route('home').'#order'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
</head>
<body>
@auth
@if($web['site_status'] !== 'live')
<div class="admin-banner">👀 Admin preview — visitors currently see the <b>{{ $web['site_status'] === 'down' ? 'offline' : 'maintenance' }}</b> page. <a href="{{ route('web-settings.edit', ['tab' => 'status']) }}">Change</a></div>
@endif
@endauth
@if($web['announcement_on'] && $web['announcement_text'] !== '')
<div class="announce">@if($web['announcement_link'] !== '')<a href="{{ $web['announcement_link'] }}" rel="noopener">{{ strtr($web['announcement_text'], $tokens) }} →</a>@else<span>{{ strtr($web['announcement_text'], $tokens) }}</span>@endif</div>
@endif

<nav class="nav" id="nav" aria-label="প্রধান মেনু">
    <div class="wrap">
        <a class="brand" href="#top">@if($logoUrl)<img class="brand-logo" src="{{ $logoUrl }}" alt="{{ $web['site_name'] }}">@else<img class="brand-ic" src="{{ \App\Support\WebSettings::icons()['192'] }}" alt="" width="32" height="32"> {{ $t('nav.brand') }}@endif</a>
        <div class="nav-links" id="nav-links">
            <a href="#features">{{ $t('nav.features') }}</a>
            @if($web['show_preview'])<a href="#preview">{{ $t('nav.preview') }}</a>@endif
            @if($web['show_audience'])<a href="#audience">{{ $t('nav.audience') }}</a>@endif
            <a href="#pricing">{{ $t('nav.pricing') }}</a>
            @if($web['show_faq'])<a href="#faq">{{ $t('nav.faq') }}</a>@endif
            @if($appLinks && $web['app_in_nav'])<a href="#app">📱 অ্যাপ</a>@endif
        </div>
        <a class="btn btn-y" href="#order">{{ $t('nav.button') }}</a>
        <button class="burger" id="burger" aria-label="মেনু খুলুন" aria-expanded="false" aria-controls="nav-links"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    </div>
</nav>

<header class="hero" id="top">
    <div class="wrap">
        <div>
            <span class="pill"><b></b> {{ $t('hero.badge') }}</span>
            <h1>{!! $h('hero.title') !!}<br><span>{{ $t('hero.highlight') }}</span></h1>
            <p class="bn-title">{{ $t('hero.subtitle') }}</p>
            <p class="desc">{!! $h('hero.description') !!}</p>
            <div class="hero-cta">
                <a class="btn btn-y" href="#order">{{ $t('hero.cta') }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                <a class="btn btn-ghost" href="{{ $web['show_preview'] ? '#preview' : '#pricing' }}">{{ $t('hero.cta2') }}</a>
            </div>
            <div class="stats">
                <div><strong>{{ $bn($totals['divisions']) }}</strong><span>{{ $t('hero.stat1') }}</span></div>
                <div><strong>{{ $bn($totals['districts']) }}</strong><span>{{ $t('hero.stat2') }}</span></div>
                <div><strong>{{ $bn($totals['upazilas']) }}</strong><span>{{ $t('hero.stat3') }}</span></div>
                <div><strong>৳{{ $priceBn }}</strong><span>{{ $t('hero.stat4') }}</span></div>
            </div>
        </div>
        <div class="book-stage" aria-hidden="true">
            <div class="sheet s2">
                <h5>Dhaka Division</h5>
                <div class="ln m"></div><div class="ln"></div><div class="ln s"></div>
                <div class="map"><svg viewBox="0 0 200 150"><path d="M30 120 C40 70 60 40 100 30 S170 50 172 95 140 140 90 132 25 140 30 120Z" fill="#cfe3d6" stroke="#2a6049" stroke-width="1.5"/><path d="M60 60 L95 80 L140 62 M95 80 L90 128 M70 110 L95 80 L150 110" fill="none" stroke="#2a6049" stroke-width="1" stroke-dasharray="3 3"/><circle cx="95" cy="80" r="4" fill="#e9b232"/></svg></div>
            </div>
            <div class="sheet s1">
                <h5>সূচিপত্র</h5>
                @foreach(array_slice($divisions, 0, 7) as $d)<div class="ln {{ $loop->odd ? 'm' : '' }}"></div>@endforeach
                <div class="ln s"></div><div class="ln"></div><div class="ln m"></div>
            </div>
            <div class="cover">
                <small>Atlas · {{ $totals['upazilas'] }} Upazilas</small>
                <h4>{!! $h('hero.title') !!}<br><span>{{ $t('hero.highlight') }}</span></h4>
                <p>{{ $t('hero.cover') }}</p>
                <div class="bottom"><b>All {{ $totals['divisions'] }} Divisions</b><span class="badge">৳{{ $priceBn }}</span></div>
            </div>
            <div class="float-chip"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 4 3 6.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5z"/><path d="M9 4v13.5M15 6.5V20"/></svg></i><div><b>দুই ক্লিকে</b>যেকোনো উপজেলা</div></div>
        </div>
    </div>
    <div class="strip">
        <div class="wrap">
            @foreach([1, 2, 3, 4] as $i)<span>{{ $t('strip.'.$i) }}</span>@endforeach
        </div>
    </div>
</header>

<main>
<section id="features">
    <div class="wrap">
        <span class="eyebrow">{{ $t('features.eyebrow') }}</span>
        <h2>{!! $h('features.title') !!}</h2>
        <p class="lead">{!! $h('features.lead') !!}</p>
        @php $featureIcons = [
            '<path d="M9 4 3 6.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5z"/><path d="M9 4v13.5M15 6.5V20"/>',
            '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>',
            '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
            '<rect x="5" y="2" width="14" height="20" rx="2.5"/><path d="M11 18h2"/>',
        ]; @endphp
        <div class="grid3">
            @foreach($featureIcons as $i => $icon)
                <article class="feat"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></span><h3>{{ $t('features.'.($i + 1).'.title') }}</h3><p>{!! $h('features.'.($i + 1).'.text') !!}</p></article>
            @endforeach
        </div>
    </div>
</section>

@if($web['show_preview'])
<section class="preview" id="preview">
    <div class="wrap">
        <span class="eyebrow">{{ $t('preview.eyebrow') }}</span>
        <h2>{!! $h('preview.title') !!}</h2>
        <p class="lead">{!! $h('preview.lead') !!}</p>
        <div class="grid4">
            @foreach($divisions as $i => $d)
                @php
                    $c = $palette[$i % count($palette)];
                    mt_srand(crc32($d['name_en']));
                    $pts = [];
                    for ($k = 0; $k < 9; $k++) { $a = $k / 9 * 6.283; $r = 38 + mt_rand(0, 18); $pts[] = round(100 + cos($a) * $r * 1.25, 1).' '.round(75 + sin($a) * $r, 1); }
                    $nodes = [];
                    for ($k = 0; $k < min(6, $d['districts']); $k++) { $nodes[] = [mt_rand(62, 138), mt_rand(46, 104)]; }
                @endphp
                <article class="div-card">
                    <div class="art">
                        <svg viewBox="0 0 200 150" aria-hidden="true">
                            <defs><pattern id="gp{{ $i }}" width="14" height="14" patternUnits="userSpaceOnUse"><path d="M14 0H0V14" fill="none" stroke="#eef1ec" stroke-width="1"/></pattern></defs>
                            <rect width="200" height="150" fill="url(#gp{{ $i }})"/>
                            <polygon points="{{ implode(' ', $pts) }}" fill="{{ $c }}22" stroke="{{ $c }}" stroke-width="1.6" stroke-linejoin="round"/>
                            @foreach($nodes as $n)<line x1="100" y1="75" x2="{{ $n[0] }}" y2="{{ $n[1] }}" stroke="{{ $c }}" stroke-width=".8" stroke-dasharray="2.5 2.5" opacity=".7"/>@endforeach
                            @foreach($nodes as $n)<circle cx="{{ $n[0] }}" cy="{{ $n[1] }}" r="3" fill="#fff" stroke="{{ $c }}" stroke-width="1.4"/>@endforeach
                            <circle cx="100" cy="75" r="5" fill="#e9b232" stroke="#fff" stroke-width="2"/>
                        </svg>
                        <span class="tag">অধ্যায় {{ $bn($i + 1) }}</span>
                    </div>
                    <div class="body">
                        <h3>{{ $d['name'] }} বিভাগ <small>{{ $d['name_en'] }}</small></h3>
                        <div class="meta">{{ $bn($d['districts']) }} জেলা · {{ $bn($d['upazilas']) }} উপজেলা</div>
                        @if($d['district_names'])
                            <details><summary>{{ $t('preview.toggle') }}</summary><div class="dlist">@foreach($d['district_names'] as $dn)<span>{{ $dn }}</span>@endforeach</div></details>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($web['show_navigate'])
<section id="navigate">
    <div class="wrap split">
        <div class="pages" aria-hidden="true">
            <div class="pg p1">Bangladesh:<br>Every Upazila<br><span>Unveiled</span></div>
            <div class="pg p2"><h6>বিভাগ সূচি</h6>@foreach($divisions as $d)<div class="row">{{ $d['name'] }} <b>{{ $bn($d['districts']) }}</b></div>@endforeach</div>
            <div class="pg p3"><h6>উপজেলা A–Z</h6>@foreach(['অভয়নগর', 'আগৈলঝাড়া', 'আটপাড়া', 'আদমদীঘি', 'আলফাডাঙ্গা', 'আশাশুনি', 'ঈশ্বরদী', 'উখিয়া'] as $u)<div class="row">{{ $u }} <b>›</b></div>@endforeach</div>
        </div>
        <div>
            <span class="eyebrow">{{ $t('navigate.eyebrow') }}</span>
            <h2>{!! $h('navigate.title') !!}</h2>
            <ul class="checks">
                @foreach([1, 2, 3, 4, 5] as $i)
                    @if($t('navigate.'.$i) !== '')<li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></i>{{ $t('navigate.'.$i) }}</li>@endif
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif

@if($web['show_audience'])
<section class="aud" id="audience">
    <div class="wrap">
        <span class="eyebrow">{{ $t('audience.eyebrow') }}</span>
        <h2>{!! $h('audience.title') !!}</h2>
        <div class="grid-aud">
            @foreach(range(1, 8) as $i)
                @php [$e, $title, $desc] = $parts('audience.'.$i); @endphp
                @if($title !== '')<div class="aud-card"><h3>@if($e !== '')<span aria-hidden="true">{{ $e }}</span>@endif{{ $title }}</h3><p>{{ $desc }}</p></div>@endif
            @endforeach
        </div>
    </div>
</section>
@endif

<section id="pricing">
    <div class="wrap">
        <div class="center"><span class="eyebrow">{{ $t('pricing.eyebrow') }}</span><h2>{!! $h('pricing.title') !!}</h2><p class="lead">{!! $h('pricing.lead') !!}</p></div>
        <div class="price-grid {{ $web['show_pricing_table'] ? '' : 'single' }}">
            <div class="price-card">
                <span class="ribbon">{{ $t('pricing.ribbon') }}</span>
                <h3>{{ $t('pricing.name') }}</h3>
                <p class="sub">{{ $t('pricing.sub') }}</p>
                <div class="amount"><small>৳</small>{{ $priceBn }}</div>
                <p class="once">{{ $t('pricing.once') }}</p>
                <ul>
                    @foreach(preg_split('/\n+/', $t('pricing.includes')) as $line)@if(trim($line) !== '')<li>{{ trim($line) }}</li>@endif @endforeach
                </ul>
                <a class="btn btn-y" href="#order">{{ $t('pricing.button') }}</a>
            </div>
@if($web['show_pricing_table'])
            <div class="table-card">
                <h3>{{ $t('pricing.table_title') }}</h3>
                <p class="sub">{{ $t('pricing.table_sub') }}</p>
                <table class="tbl">
                    <thead><tr><th>বিভাগ</th><th>জেলা</th><th>উপজেলা</th></tr></thead>
                    <tbody>@foreach($divisions as $d)<tr><td>{{ $d['name'] }}</td><td>{{ $bn($d['districts']) }}</td><td>{{ $bn($d['upazilas']) }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><td>মোট</td><td>{{ $bn($totals['districts']) }}</td><td>{{ $bn($totals['upazilas']) }}</td></tr></tfoot>
                </table>
            </div>
@endif
        </div>
    </div>
</section>

@if($appLinks)
<section class="app-sec" id="app">
    <div class="wrap">
        <div class="app-box">
            <div class="app-txt">
                <span class="app-name"><img src="{{ \App\Support\WebSettings::icons()['192'] }}" alt="" width="34" height="34"><b>{{ $web['app_name'] }}</b>
                    @if($web['app_rating'] !== '')<em>★ {{ $bn($web['app_rating']) }}</em>@endif
                    @if($web['app_downloads'] !== '')<em>{{ $web['app_downloads'] }}</em>@endif
                </span>
                <h2>{{ strtr($web['app_title'], $tokens) }}</h2>
                <p>{{ strtr($web['app_text'], $tokens) }}</p>
                @include('partials.store-badges')
            </div>
            <div class="phone" aria-hidden="true">
                <div class="screen">
                    <div class="sbar"><span>৯:৪১</span><i></i></div>
                    <div class="ahead"><img src="{{ \App\Support\WebSettings::icons()['192'] }}" alt="" width="22" height="22"><b>{{ $web['app_name'] }}</b></div>
                    <div class="asearch">🔍 উপজেলা খুঁজুন…</div>
                    <div class="amap"><svg viewBox="0 0 200 110"><path d="M40 95 C48 55 70 25 108 18 S175 40 168 75 132 108 92 102 36 112 40 95Z" fill="#cfe3d6" stroke="#2a6049" stroke-width="1.4"/><path d="M70 50 L105 62 L145 48 M105 62 L100 100 M78 88 L105 62 L152 86" fill="none" stroke="#2a6049" stroke-width=".9" stroke-dasharray="3 3"/><circle cx="105" cy="62" r="5" fill="#e9b232" stroke="#fff" stroke-width="2"/></svg></div>
                    @foreach(array_slice($divisions, 0, 4) as $d)
                        <div class="arow"><span>{{ $d['name'] }}</span><small>{{ $bn($d['upazilas']) }} উপজেলা</small></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<section id="order" style="background:#fff;border-block:1px solid var(--line)">
    <div class="wrap">
        <span class="eyebrow">{{ $t('order.eyebrow') }}</span>
        <h2>{!! $h('order.title') !!}</h2>
        <div class="steps">
            @foreach([1, 2, 3] as $i)
                @php [$st, $sd] = $parts('order.step'.$i); @endphp
                <div class="step"><b>{{ $bn($i) }}</b><h3>{{ $st }}</h3><p>{{ $sd }}</p>@if($i === 2 && $bkash)<span class="num">বিকাশ: {{ $bkash }}</span>@endif</div>
            @endforeach
        </div>

        <div class="order-box">
            <div class="form-card">
                @if($orderId)
                    <div class="success" role="status">
                        <div class="tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></div>
                        <h3>{{ $t('order.success_title') }}</h3>
                        <p>{!! $h('order.success_text') !!}</p>
                        @if($orderId > 0)<span class="oid">অর্ডার নং #{{ $bn($orderId) }}</span>@endif
                    </div>
                @elseif(! $ordersOpen)
                    <div class="success"><h3>{{ $t('order.closed') }}</h3><p>অনুগ্রহ করে পরে আবার চেষ্টা করুন@if($phone), অথবা {{ $phone }} নম্বরে ফোন করুন@endif।</p></div>
                @else
                    @if($err->any())<div class="alert bad" role="alert">⚠️ অনুগ্রহ করে নিচের তথ্যগুলো ঠিক করুন।</div>@endif
                    <form method="post" action="{{ route('landing.order') }}" id="order-form" novalidate>
                        @csrf
                        <div class="hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                        <div class="fgrid">
                            <div>
                                <label for="o-name">আপনার নাম <em>*</em></label>
                                <input class="in {{ $err->has('name') ? 'bad' : '' }}" id="o-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" placeholder="পুরো নাম">
                                @if($err->has('name'))<p class="ferr">{{ $err->first('name') }}</p>@endif
                            </div>
                            <div>
                                <label for="o-phone">মোবাইল নম্বর <em>*</em></label>
                                <input class="in {{ $err->has('phone') ? 'bad' : '' }}" id="o-phone" name="phone" value="{{ old('phone') }}" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX">
                                @if($err->has('phone'))<p class="ferr">{{ $err->first('phone') }}</p>@endif
                            </div>
                            <div class="full">
                                <label for="o-district">জেলা</label>
                                <input class="in" id="o-district" name="district" value="{{ old('district') }}" maxlength="120" list="district-list" placeholder="আপনার জেলা লিখুন বা বেছে নিন" autocomplete="off">
                                <datalist id="district-list">@foreach($districtOptions as $o)<option value="{{ $o }}">@endforeach</datalist>
                            </div>
                            <div class="full">
                                <label for="o-address">ঠিকানা / বার্তা</label>
                                <textarea class="in" id="o-address" name="address" maxlength="1000" placeholder="কিছু জানাতে চাইলে লিখুন (ঐচ্ছিক)">{{ old('address') }}</textarea>
                            </div>
                        </div>
                        <div class="form-foot">
                            <button class="btn btn-g" type="submit" id="order-btn">{{ $t('order.button') }}</button>
                            <small>{{ $t('order.privacy') }}</small>
                        </div>
                    </form>
                @endif
            </div>
            <aside class="side">
                @if($phone)
                    <div class="contact">
                        <small>{{ $t('order.contact_label') }}</small>
                        <a class="tel" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
                        <a class="btn btn-y" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $t('order.call') }}</a>
                    </div>
                @endif
                <div class="assure">
                    @foreach([1 => '✓', 2 => '৳', 3 => '⚡'] as $i => $icon)
                        @php [$at, $ad] = $parts('order.assure'.$i); @endphp
                        @if($at !== '')<div><i>{{ $icon }}</i><span><b>{{ $at }}</b>{{ $ad }}</span></div>@endif
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</section>

@if($web['show_faq'])
<section id="faq">
    <div class="wrap">
        <div class="center"><span class="eyebrow">{{ $t('faq.eyebrow') }}</span><h2>{!! $h('faq.title') !!}</h2></div>
        <div class="faq">
            @foreach(range(1, 6) as $i)
                @if($t('faq.'.$i.'.q') !== '')<details><summary>{{ $t('faq.'.$i.'.q') }}</summary><p>{!! $h('faq.'.$i.'.a') !!}</p></details>@endif
            @endforeach
        </div>
    </div>
</section>
@endif

@if($web['show_cta'])
<div class="cta">
    <div class="wrap">
        <div class="cta-box">
            <h2>{!! $h('cta.title') !!}</h2>
            <p>{!! $h('cta.text') !!}</p>
            <a class="btn btn-y" href="#order">{{ $t('cta.button') }}</a>
        </div>
    </div>
</div>
@endif
</main>

<footer>
    <div class="wrap">
        <div class="f-brand">
            @if($logoUrl)<img class="brand-logo" src="{{ $logoUrl }}" alt="{{ $web['site_name'] }}">@else<b>{{ $web['site_name'] }}</b>@endif
            <span>© {{ $bn(now()->year) }} {{ $t('footer.text') }}</span>
        </div>
        @if($web['show_contact_footer'])
            <div class="f-contact">
                @if($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">📞 {{ $phone }}</a>@endif
                @if($web['support_email'] !== '')<a href="mailto:{{ $web['support_email'] }}">✉️ {{ $web['support_email'] }}</a>@endif
                @if($web['whatsapp'] !== '')<a href="https://wa.me/{{ ltrim($web['whatsapp'], '+') }}" target="_blank" rel="noopener">💬 WhatsApp</a>@endif
                @if($web['facebook_url'] !== '')<a href="{{ $web['facebook_url'] }}" target="_blank" rel="noopener">Facebook</a>@endif
                @if($web['youtube_url'] !== '')<a href="{{ $web['youtube_url'] }}" target="_blank" rel="noopener">YouTube</a>@endif
                @if($web['address'] !== '')<span>📍 {{ $web['address'] }}</span>@endif
                @if($web['office_hours'] !== '')<span>🕘 {{ $web['office_hours'] }}</span>@endif
            </div>
        @endif
        @if($appLinks && $web['app_in_footer'])<div class="f-stores">@include('partials.store-badges', ['small' => true])</div>@endif
        @if($web['show_admin_link'])<a class="f-admin" href="{{ auth()->check() ? route('dashboard') : route('login') }}" rel="nofollow">Admin</a>@endif
    </div>
</footer>

@if($web['whatsapp_button'] && $web['whatsapp'] !== '')
<a class="wa-float" href="https://wa.me/{{ ltrim($web['whatsapp'], '+') }}?text={{ rawurlencode('আমি বইটি সম্পর্কে জানতে চাই') }}" target="_blank" rel="noopener" aria-label="WhatsApp-এ মেসেজ করুন"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5.1-4.5-.1-.2-1.2-1.6-1.2-3s.8-2.2 1-2.5c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .7.5l1 2.3c.1.2.1.4 0 .6l-.4.6-.5.5c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1l1-1.2c.2-.3.4-.2.7-.1l2.2 1c.3.2.5.2.6.4.1.1.1.8-.1 1.4z"/></svg></a>
@endif
@if($web['mobile_buy_bar'])
<div class="mbar" id="mbar">
    <div><strong>৳{{ $priceBn }}</strong><small>{{ $t('mbar.text') }}</small></div>
    <a class="btn btn-y" href="#order">{{ $t('nav.button') }}</a>
</div>
@endif

<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const nav = document.getElementById('nav'), burger = document.getElementById('burger');
    burger.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        burger.setAttribute('aria-expanded', open);
    });
    document.querySelectorAll('#nav-links a').forEach(a => a.addEventListener('click', () => { nav.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); }));

    // Mobile sticky buy bar: show after the hero, hide while the order form is on screen.
    const bar = document.getElementById('mbar'), hero = document.getElementById('top'), order = document.getElementById('order');
    if (bar && 'IntersectionObserver' in window) {
        let heroVisible = true, orderVisible = false;
        const sync = () => bar.classList.toggle('show', !heroVisible && !orderVisible);
        new IntersectionObserver(([e]) => { heroVisible = e.isIntersecting; sync(); }).observe(hero);
        new IntersectionObserver(([e]) => { orderVisible = e.isIntersecting; sync(); }).observe(order);
    }

    const form = document.getElementById('order-form');
    if (form) form.addEventListener('submit', e => {
        const btn = document.getElementById('order-btn');
        if (btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true; btn.textContent = 'পাঠানো হচ্ছে…';
    });
    @if($err->any() || $orderId)
    order.scrollIntoView();
    @endif
})();
</script>
</body>
</html>
