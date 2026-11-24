@extends('layouts.app')
@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px"><button class="icon-btn menu-btn" data-menu-toggle aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button><div><p class="eyebrow">Management</p><h1>Location Sets</h1><p class="sub">Create and manage location-based records.</p></div></div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button><a class="logout" href="{{ route('location-sets.create') }}" style="text-decoration:none;background:var(--primary);border-color:var(--primary);color:white">+ Add location set</a></div>
</header>
<section class="table-card">
    @php($missingDivisionImages = \App\Models\Division::whereNull('image_path')->orWhere('image_path', '')->count())
    @if($missingDivisionImages > 0)<div style="margin:18px 23px 0;padding:13px 15px;border-radius:9px;background:#fff7e6;color:#9a6700;border:1px solid #f3d28a"><strong>Setup required:</strong> Upload map images for all {{ $missingDivisionImages }} remaining divisions before adding Location Sets. <a href="{{ route('locations.divisions') }}" style="color:#8a5a00;font-weight:700">Go to Divisions →</a></div>@endif
    @if(session('success'))<div style="margin:18px 23px 0;padding:11px 14px;border-radius:9px;background:#e9f8ef;color:#168346">{{ session('success') }}</div>@endif
    <div class="table-head"><div><h2>Location set directory</h2><p class="sub">{{ $items->count() }} records{{ $search ? ' for “'.$search.'”' : '' }}</p></div><form method="get" style="display:flex;gap:8px"><input type="search" name="q" value="{{ $search }}" placeholder="Search location names" style="padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink)"><button class="logout" type="submit">Search</button></form></div>
    <div class="table-wrap"><table><thead><tr><th>#</th><th>DIVISION</th><th>DISTRICT</th><th>THANA / UPAZILA</th><th>STATUS</th><th>REMARKS</th><th>IMAGE</th><th>ACTION</th></tr></thead><tbody>
    @forelse($items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item->division->name }}<small style="display:block;color:var(--muted)">{{ $item->division->name_bn }}</small></td><td>{{ $item->district->name }}<small style="display:block;color:var(--muted)">{{ $item->district->name_bn }}</small></td><td>{{ $item->upazila->name }}<small style="display:block;color:var(--muted)">{{ $item->upazila->name_bn }}</small></td><td><span class="badge" style="{{ $item->status === 'inactive' ? 'background:#fff0f0;color:#c53030' : '' }}">{{ ucfirst($item->status) }}</span></td><td>{{ $item->remarks ? \Illuminate\Support\Str::limit($item->remarks, 35) : '—' }}</td><td>@if($item->image_path)<button type="button" class="image-preview-trigger" data-image="{{ route('location-sets.image', $item) }}" data-title="{{ $item->upazila->name }} · {{ $item->district->name }}" aria-label="View larger image"><img src="{{ route('location-sets.image', $item) }}" alt="Location image" style="width:42px;height:42px;border-radius:8px;object-fit:cover;display:block"></button>@else—@endif</td><td><div style="display:flex;gap:8px"><a href="{{ route('location-sets.edit', $item) }}" style="color:var(--primary);font-weight:700;text-decoration:none">Edit</a><form method="post" action="{{ route('location-sets.destroy', $item) }}" data-confirm="Delete this location set?">@csrf @method('DELETE')<button type="submit" style="border:0;background:none;color:#d34b4b;cursor:pointer;font-weight:700">Delete</button></form></div></td></tr>
    @empty<tr><td colspan="8">No location sets found.</td></tr>@endforelse
    </tbody></table></div>
</section>
<div class="image-modal" id="image-modal" role="dialog" aria-modal="true" aria-labelledby="image-modal-title" hidden>
    <div class="image-modal-card">
        <button type="button" class="image-modal-close" id="image-modal-close" aria-label="Close image preview">&times;</button>
        <p id="image-modal-title" class="image-modal-title"></p>
        <img id="image-modal-image" src="" alt="Large location image">
        <a id="image-modal-open" href="#" target="_blank" rel="noopener">Open original image</a>
    </div>
</div>
<style>
    .image-preview-trigger { border:0; padding:0; border-radius:9px; background:transparent; cursor:zoom-in; transition:transform .18s, box-shadow .18s; }
    .image-preview-trigger:hover { transform:scale(1.08); box-shadow:0 5px 16px #2563eb44; }
    .image-modal[hidden] { display:none; }
    .image-modal { position:fixed; inset:0; z-index:100; display:grid; place-items:center; padding:24px; background:#071127cc; backdrop-filter:blur(5px); }
    .image-modal-card { position:relative; width:min(820px,100%); max-height:calc(100vh - 48px); padding:22px; border:1px solid var(--line); border-radius:16px; background:var(--surface); box-shadow:0 25px 80px #0008; text-align:center; }
    .image-modal-card img { display:block; width:auto; max-width:100%; max-height:calc(100vh - 150px); margin:10px auto 15px; border-radius:10px; object-fit:contain; }
    .image-modal-close { position:absolute; top:9px; right:12px; width:34px; height:34px; border:0; border-radius:50%; background:var(--surface-2); color:var(--ink); font-size:25px; line-height:1; cursor:pointer; }
    .image-modal-title { margin:0 38px; color:var(--ink); font-weight:700; }
    #image-modal-open { color:var(--primary); font-weight:700; text-decoration:none; font-size:13px; }
</style>
<script nonce="{{ Vite::cspNonce() }}">
    const imageModal = document.getElementById('image-modal');
    const modalImage = document.getElementById('image-modal-image');
    const modalTitle = document.getElementById('image-modal-title');
    const modalOpen = document.getElementById('image-modal-open');
    const closeImageModal = () => { imageModal.hidden = true; modalImage.src = ''; };
    document.querySelectorAll('.image-preview-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            modalImage.src = trigger.dataset.image;
            modalTitle.textContent = trigger.dataset.title;
            modalOpen.href = trigger.dataset.image;
            imageModal.hidden = false;
        });
    });
    document.getElementById('image-modal-close').addEventListener('click', closeImageModal);
    imageModal.addEventListener('click', (event) => { if (event.target === imageModal) closeImageModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !imageModal.hidden) closeImageModal(); });
</script>
@endsection
