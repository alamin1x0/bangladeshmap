@extends('layouts.app')
@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px"><button class="icon-btn menu-btn" data-menu-toggle aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button><div><p class="eyebrow">Workspace</p><h1>{{ $title }}</h1><p class="sub">{{ $description }}</p></div></div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>
<section class="table-card" style="padding:38px;text-align:center"><div class="card-icon" style="margin:0 auto 15px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2 2"/></svg></div><h2>{{ $title }} module</h2><p class="sub">This workspace section is ready for your next management workflow.</p></section>
@endsection
