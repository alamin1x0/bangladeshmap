@extends('layouts.app')
@section('content')
@php
    $photoCount = $items->whereNotNull('image_path')->where('image_path', '<>', '')->count();
    $missingPhotoCount = $items->count() - $photoCount;
    $photoFilter = in_array(request('photo'), ['added', 'missing'], true) ? request('photo') : null;
    $rows = match ($photoFilter) {
        'added' => $items->filter(fn ($i) => filled($i->image_path))->values(),
        'missing' => $items->filter(fn ($i) => blank($i->image_path))->values(),
        default => $items,
    };
    $filterKey = $type === 'districts' ? 'division' : ($type === 'upazilas' ? 'district' : null);
    $baseQuery = ['q' => $search ?: null];
    if ($filterKey) {
        $baseQuery[$filterKey] = ($selected ?? null) ?: null;
    }
    $chipUrl = fn ($photo) => route("locations.{$type}", array_filter(array_merge($baseQuery, ['photo' => $photo])));
    $singular = ['divisions' => 'division', 'districts' => 'district', 'upazilas' => 'thana'][$type];
    $tones = ['#2a78d6', '#1baf7a', '#6250d6', '#eb6834', '#e87ba4', '#008300'];
    $tone = fn ($name) => $tones[crc32((string) $name) % count($tones)];
    $childLink = fn ($item) => match ($type) {
        'divisions' => [route('locations.districts', ['division' => $item->id]), $item->districts_count.' '.\Illuminate\Support\Str::plural('district', $item->districts_count)],
        'districts' => [route('locations.upazilas', ['district' => $item->id]), $item->upazilas_count.' '.\Illuminate\Support\Str::plural('thana', $item->upazilas_count)],
        default => [null, null],
    };
    $selectedName = isset($filter, $selected) && $selected ? optional($filter->firstWhere('id', $selected))->name : null;
@endphp
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:10px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div><p class="eyebrow">Bangladesh locations</p><h1>{{ $title }}</h1><p class="sub">{{ number_format($items->count()) }} {{ \Illuminate\Support\Str::plural($singular, $items->count()) }}{{ $selectedName ? ' in '.$selectedName : '' }} · names, codes and map photos</p></div>
    </div>
    <div class="top-actions">@if($type !== 'divisions')<a class="loc-btn" href="{{ route('locations.create', array_filter([$type, ($type === 'districts' ? 'division' : 'district') => $selected ?? null])) }}" style="text-decoration:none">+ Add {{ $type === 'districts' ? 'district' : 'thana' }}</a>@endif<button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

@if(session('error'))
    <div class="loc-flash" role="alert" style="background:#fdecec;color:#b42318;border-color:#f5c2c0">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="loc-flash" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>{{ session('success') }}</div>
@endif

<section class="table-card">
    <div class="loc-toolbar">
        <div class="loc-title">
            <h2>{{ $title }} directory</h2>
            <p class="sub">{{ number_format($rows->count()) }} shown{{ $search ? ' for “'.$search.'”' : '' }}{{ $selectedName ? ' · '.$selectedName : '' }}</p>
        </div>
        <form method="get" class="loc-search" role="search">
            @if($photoFilter)<input type="hidden" name="photo" value="{{ $photoFilter }}">@endif
            <div class="loc-search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Search English or বাংলা name" aria-label="Search {{ strtolower($title) }}" maxlength="100">
            </div>
            @if(isset($filter))
                <select class="loc-select" name="{{ $filterKey }}" aria-label="Filter by {{ $filterKey }}" data-autosubmit>
                    <option value="">All {{ $type === 'districts' ? 'divisions' : 'districts' }}</option>
                    @foreach($filter as $option)<option value="{{ $option->id }}" @selected($selected === $option->id)>{{ $option->name }}{{ $type === 'upazilas' ? ' · '.$option->division->name : '' }}</option>@endforeach
                </select>
            @endif
            <button type="submit" class="loc-btn">Search</button>
            @if($search || ($selected ?? null) || $photoFilter)<a href="{{ route("locations.{$type}") }}" class="loc-btn ghost" title="Clear all filters">Clear</a>@endif
        </form>
    </div>

    <nav class="location-summary" aria-label="Filter by map photo">
        <a class="location-chip total {{ $photoFilter === null ? 'on' : '' }}" href="{{ $chipUrl(null) }}"><span class="chip-dot"></span><div><strong>{{ $items->count() }}</strong><small>All<span class="chip-extra"> {{ strtolower($title) }}</span></small></div></a>
        <a class="location-chip added {{ $photoFilter === 'added' ? 'on' : '' }}" href="{{ $chipUrl('added') }}"><span class="chip-dot"></span><div><strong>{{ $photoCount }}</strong><small><span class="chip-extra">Photo </span>added</small></div></a>
        <a class="location-chip missing {{ $photoFilter === 'missing' ? 'on' : '' }}" href="{{ $chipUrl('missing') }}"><span class="chip-dot"></span><div><strong>{{ $missingPhotoCount }}</strong><small><span class="chip-extra">Photo </span>missing</small></div></a>
        @if($items->count() > 0)
            <span class="photo-progress" title="{{ $photoCount }} of {{ $items->count() }} have a map photo"><span class="bar"><i style="width:{{ round($photoCount / $items->count() * 100) }}%"></i></span>{{ round($photoCount / $items->count() * 100) }}% complete</span>
        @endif
    </nav>

    @if($rows->isEmpty())
        <div class="loc-empty">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span>
            <strong>No {{ strtolower($title) }} found</strong>
            <span>{{ $search || ($selected ?? null) || $photoFilter ? 'Try another search or clear the filters.' : 'Run the Bangladesh location seeder to load data.' }}</span>
            @if($search || ($selected ?? null) || $photoFilter)<a class="loc-btn" href="{{ route("locations.{$type}") }}" style="margin-top:6px">Clear filters</a>@endif
        </div>
    @else
    <div class="table-wrap"><table class="loc-table"><thead><tr><th>#</th><th>NAME</th>@if($type !== 'divisions')<th>PARENT</th>@endif<th>CODE</th><th>MAP PHOTO</th>@if($type !== 'upazilas')<th>CONTAINS</th>@endif<th></th></tr></thead><tbody>
    @foreach($rows as $item)
        @php [$childUrl, $childText] = $childLink($item); @endphp
        <tr>
            <td class="idx">{{ $loop->iteration }}</td>
            <td><div class="loc-name"><span class="loc-avatar" style="background:{{ $tone($item->name) }}">{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}</span><div><strong>{{ $item->name }}</strong><small class="{{ $item->name_bn ? '' : 'missing-bn' }}">{{ $item->name_bn ?: 'Bangla name not set' }}</small></div></div></td>
            @if($type === 'districts')<td><a class="parent-link" href="{{ route('locations.districts', ['division' => $item->division_id]) }}">{{ $item->division->name }}</a></td>
            @elseif($type === 'upazilas')<td><a class="parent-link" href="{{ route('locations.upazilas', ['district' => $item->district_id]) }}">{{ $item->district->name }}</a><small class="parent-sub">{{ $item->district->division->name }}</small></td>@endif
            <td><span class="code-pill">{{ $item->code ?: '—' }}</span></td>
            <td>@if($item->image_path)<div class="map-photo-actions"><button type="button" class="location-map-thumb" data-map-url="{{ route('locations.image', [$type, $item->id]) }}" data-map-title="{{ $item->name }}" aria-label="View {{ $item->name }} map"><img src="{{ route('locations.image', [$type, $item->id]) }}" alt="{{ $item->name }} map photo" loading="lazy"></button><button type="button" class="photo-remove-link" data-remove-url="{{ route('locations.image.remove', [$type, $item->id]) }}" data-remove-name="{{ $item->name }}">Remove</button></div>@else<button type="button" class="map-missing upload-map-button" data-upload-url="{{ route('locations.update', [$type, $item->id]) }}" data-upload-name="{{ $item->name }}" data-upload-bn="{{ $item->name_bn }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Upload map</button>@endif</td>
            @if($type !== 'upazilas')<td><a class="child-pill" href="{{ $childUrl }}" title="View {{ $childText }}">{{ $childText }} <span aria-hidden="true">→</span></a></td>@endif
            <td class="act-cell"><a class="act-edit" href="{{ route('locations.edit', [$type, $item->id]) }}" title="Edit {{ $item->name }}" aria-label="Edit {{ $item->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/></svg><span>Edit</span></a>
                @if($type !== 'divisions')
                <button type="button" class="act-edit act-del" data-delete-url="{{ route('locations.destroy', [$type, $item->id]) }}" data-delete-kind="{{ $type === 'districts' ? 'district' : 'thana' }}" data-delete-name="{{ $item->name }}" @if($type === 'districts' && $item->upazilas_count) data-delete-impact="This will also delete {{ $item->upazilas_count }} {{ \Illuminate\Support\Str::plural('thana', $item->upazilas_count) }} under it." @endif title="Delete {{ $item->name }}" aria-label="Delete {{ $item->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg><span>Delete</span></button>
                @endif</td>
        </tr>
    @endforeach
    </tbody></table></div>
    @endif
</section>
<div class="map-modal" id="map-modal" hidden role="dialog" aria-modal="true" aria-labelledby="map-modal-title">
    <div class="map-modal-card">
        <button type="button" class="map-modal-close" aria-label="Close map preview">&times;</button>
        <p id="map-modal-title"></p>
        <img id="map-modal-image" alt="Location map preview">
        <a id="map-modal-original" target="_blank" rel="noopener">Open original</a>
    </div>
</div>
<div class="photo-action-modal" id="upload-modal" hidden role="dialog" aria-modal="true" aria-labelledby="upload-modal-title">
    <div class="photo-action-card">
        <button type="button" class="photo-action-close" data-close-photo-modal aria-label="Close upload dialog">&times;</button>
        <div class="photo-action-icon upload-icon">↑</div><h2 id="upload-modal-title">Upload map photo</h2><p>Choose a clear map image for <strong id="upload-modal-name"></strong>.</p>
        <form id="upload-photo-form" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PUT"><input type="hidden" name="name" id="upload-name"><input type="hidden" name="name_bn" id="upload-name-bn"><label class="photo-drop-zone" id="photo-drop-zone" for="upload-image"><span class="drop-cloud">↑</span><strong>Drag & drop your photo here</strong><span>or click to browse · JPG, PNG or WEBP · up to 100MB · auto-compressed to under 500KB</span><em id="upload-file-name">No photo selected</em></label><input id="upload-image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" required><div class="upload-progress" id="upload-progress" hidden><div class="progress-label"><span id="upload-progress-text">Uploading photo</span><strong id="upload-percent">0%</strong></div><div class="progress-track"><span id="upload-progress-bar"></span></div></div><p class="upload-error" id="upload-error" hidden></p><div class="photo-action-buttons"><button type="button" class="logout" data-close-photo-modal>Cancel</button><button type="submit" class="photo-primary" id="upload-submit">Upload photo</button></div></form>
    </div>
</div>
<div class="photo-action-modal" id="remove-modal" hidden role="dialog" aria-modal="true" aria-labelledby="remove-modal-title">
    <div class="photo-action-card">
        <button type="button" class="photo-action-close" data-close-photo-modal aria-label="Close remove dialog">&times;</button>
        <div class="photo-action-icon remove-icon">!</div><h2 id="remove-modal-title">Remove map photo?</h2><p>The map photo for <strong id="remove-modal-name"></strong> will be removed. You can upload a new one later.</p>
        <form id="remove-photo-form" method="post"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><div class="photo-action-buttons"><button type="button" class="logout" data-close-photo-modal>Keep photo</button><button type="submit" class="photo-danger">Remove photo</button></div></form>
    </div>
</div>
@include('locations._delete-modal')
<style>
    .act-cell{white-space:nowrap}.act-cell>*{margin-left:6px}
    .act-del{background:transparent;color:#d92d20;border-color:#f3c9c6;cursor:pointer}.act-del:hover{background:#fff0f0;border-color:#d92d20;color:#b42318}
    .location-map-thumb { width:68px;height:48px;padding:0;border:1px solid var(--line);border-radius:8px;background:var(--surface-2);overflow:hidden;cursor:zoom-in;transition:.2s; }
    .location-map-thumb:hover { transform:scale(1.08); box-shadow:0 6px 18px #2563eb44; }
    .location-map-thumb img { display:block;width:100%;height:100%;object-fit:cover; }
    .map-photo-actions { display:flex;align-items:center;gap:7px;flex-wrap:wrap; }
    .map-missing { display:inline-flex;align-items:center;padding:7px 9px;border:1px dashed var(--line);border-radius:7px;color:var(--primary);font-size:12px;font-weight:700;text-decoration:none;background:transparent;cursor:pointer; }
    .photo-remove-link { border:0;background:transparent;color:#d45454;font-size:11px;font-weight:700;padding:3px;cursor:pointer; }
    .map-modal[hidden] { display:none; }
    .map-modal { position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:24px;background:#071127cc;backdrop-filter:blur(5px); }
    .map-modal-card { position:relative;width:min(850px,100%);padding:24px;border:1px solid var(--line);border-radius:16px;background:var(--surface);text-align:center;box-shadow:0 25px 80px #0008; }
    .map-modal-card p { margin:0 35px 14px;color:var(--ink);font-weight:700; }
    .map-modal-card img { display:block;max-width:100%;max-height:70vh;margin:auto;border-radius:10px;object-fit:contain; }
    .map-modal-card a { display:inline-block;margin-top:14px;color:var(--primary);font-weight:700;text-decoration:none; }
    .map-modal-close { position:absolute;top:9px;right:12px;width:34px;height:34px;border:0;border-radius:50%;background:var(--surface-2);color:var(--ink);font-size:25px;cursor:pointer; }
    .photo-action-modal[hidden] { display:none; }
    .photo-action-modal { position:fixed;inset:0;z-index:110;display:grid;place-items:center;padding:20px;background:#071127b8;backdrop-filter:blur(6px); }
    .photo-action-card { position:relative;width:min(440px,100%);padding:30px;border:1px solid var(--line);border-radius:18px;background:var(--surface);text-align:center;box-shadow:0 25px 80px #0008; }
    .photo-action-card h2 { margin:12px 0 8px;color:var(--ink);font-size:21px; }
    .photo-action-card p { margin:0;color:var(--muted);font-size:13px;line-height:1.55; }
    .photo-action-card p strong { color:var(--ink); }
    .photo-action-close { position:absolute;top:10px;right:12px;width:32px;height:32px;border:0;border-radius:50%;background:var(--surface-2);color:var(--ink);font-size:23px;cursor:pointer; }
    .photo-action-icon { display:grid;place-items:center;width:52px;height:52px;margin:auto;border-radius:16px;font-size:25px;font-weight:800; }
    .upload-icon { background:#e9f1ff;color:var(--primary); }.remove-icon { background:#fff0f0;color:#d45454; }
    .photo-drop-zone { display:flex;flex-direction:column;align-items:center;gap:5px;margin:22px 0 8px;padding:20px 14px;border:1.5px dashed var(--primary);border-radius:13px;background:var(--primary-soft);color:var(--primary);font-weight:700;cursor:pointer;transition:.2s; }
    .photo-drop-zone.drag-over { border-style:solid;background:#dce9ff;transform:scale(1.01); }
    .photo-drop-zone > span:not(.drop-cloud) { color:var(--muted);font-size:11px;font-weight:400; }.photo-drop-zone em { max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ink);font-size:12px;font-style:normal;font-weight:600; }
    .drop-cloud { display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:var(--surface);font-size:20px; }
    .photo-action-card input[type=file] { position:absolute;width:1px;height:1px;opacity:0;pointer-events:none; }
    .upload-progress { margin:16px 0 2px;text-align:left; }.progress-label { display:flex;justify-content:space-between;margin-bottom:7px;color:var(--muted);font-size:11px; }.progress-label strong { color:var(--primary);font-size:13px; }.progress-track { height:8px;overflow:hidden;border-radius:99px;background:var(--surface-2); }.progress-track span { display:block;width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,#2563eb,#45b3ff);transition:width .15s; }.upload-error { margin:12px 0 0;color:#d45454;font-size:12px;font-weight:600; }
    .photo-action-buttons { display:flex;justify-content:center;gap:9px;margin-top:22px; }.photo-primary,.photo-danger { border:0;border-radius:9px;padding:10px 15px;color:#fff;font-weight:700;cursor:pointer; }.photo-primary { background:var(--primary); }.photo-danger { background:#d45454; }
</style>
<script nonce="{{ Vite::cspNonce() }}">
    const mapModal = document.getElementById('map-modal');
    const mapModalImage = document.getElementById('map-modal-image');
    const mapModalTitle = document.getElementById('map-modal-title');
    const mapModalOriginal = document.getElementById('map-modal-original');
    const closeMapModal = () => { mapModal.hidden = true; mapModalImage.src = ''; };
    document.querySelectorAll('.location-map-thumb').forEach((button) => button.addEventListener('click', () => {
        mapModalImage.src = button.dataset.mapUrl;
        mapModalTitle.textContent = `${button.dataset.mapTitle} map photo`;
        mapModalOriginal.href = button.dataset.mapUrl;
        mapModal.hidden = false;
    }));
    document.querySelector('.map-modal-close')?.addEventListener('click', closeMapModal);
    mapModal?.addEventListener('click', (event) => { if (event.target === mapModal) closeMapModal(); });
    const uploadModal = document.getElementById('upload-modal');
    const removeModal = document.getElementById('remove-modal');
    const closePhotoModals = () => document.querySelectorAll('.photo-action-modal').forEach((modal) => { modal.hidden = true; });
    document.querySelectorAll('.upload-map-button').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('upload-photo-form').action = button.dataset.uploadUrl;
        document.getElementById('upload-modal-name').textContent = button.dataset.uploadName;
        document.getElementById('upload-name').value = button.dataset.uploadName;
        document.getElementById('upload-name-bn').value = button.dataset.uploadBn || '';
        document.getElementById('upload-image').value = '';
        document.getElementById('upload-file-name').textContent = 'No photo selected';
        document.getElementById('upload-progress').hidden = true;
        document.getElementById('upload-error').hidden = true;
        document.getElementById('upload-progress-bar').style.width = '0%';
        document.getElementById('upload-percent').textContent = '0%';
        document.getElementById('upload-submit').disabled = false;
        uploadModal.hidden = false;
    }));
    document.querySelectorAll('.photo-remove-link').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('remove-photo-form').action = button.dataset.removeUrl;
        document.getElementById('remove-modal-name').textContent = button.dataset.removeName;
        removeModal.hidden = false;
    }));
    document.querySelectorAll('[data-close-photo-modal]').forEach((button) => button.addEventListener('click', closePhotoModals));
    document.querySelectorAll('.photo-action-modal').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closePhotoModals(); }));
    const uploadForm = document.getElementById('upload-photo-form');
    const uploadInput = document.getElementById('upload-image');
    const dropZone = document.getElementById('photo-drop-zone');
    const fileName = document.getElementById('upload-file-name');
    const uploadProgress = document.getElementById('upload-progress');
    const uploadProgressBar = document.getElementById('upload-progress-bar');
    const uploadPercent = document.getElementById('upload-percent');
    const uploadError = document.getElementById('upload-error');
    const uploadSubmit = document.getElementById('upload-submit');
    const setUploadFile = (file) => {
        if (!file) return;
        const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type) || file.size > 100 * 1024 * 1024) {
            uploadError.textContent = 'Please choose JPG, PNG or WEBP up to 100MB.';
            uploadError.hidden = false;
            uploadInput.value = '';
            fileName.textContent = 'No photo selected';
            return;
        }
        uploadError.hidden = true;
        fileName.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            uploadInput.files = transfer.files;
        } catch (error) {
            uploadError.textContent = 'This browser could not attach the dropped file. Please browse instead.';
            uploadError.hidden = false;
        }
    };
    uploadInput.addEventListener('change', () => setUploadFile(uploadInput.files[0]));
    ['dragenter', 'dragover'].forEach((eventName) => dropZone.addEventListener(eventName, (event) => { event.preventDefault(); dropZone.classList.add('drag-over'); }));
    ['dragleave', 'drop'].forEach((eventName) => dropZone.addEventListener(eventName, (event) => { event.preventDefault(); dropZone.classList.remove('drag-over'); }));
    dropZone.addEventListener('drop', (event) => setUploadFile(event.dataTransfer.files[0]));
    uploadForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const file = uploadInput.files[0];
        if (!file) { uploadError.textContent = 'Please choose a map photo first.'; uploadError.hidden = false; return; }
        const request = new XMLHttpRequest();
        const formData = new FormData(uploadForm);
        uploadProgress.hidden = false;
        uploadError.hidden = true;
        uploadSubmit.disabled = true;
        uploadSubmit.textContent = 'Uploading...';
        request.upload.addEventListener('progress', (progressEvent) => {
            if (!progressEvent.lengthComputable) return;
            const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
            uploadProgressBar.style.width = `${percent}%`;
            uploadPercent.textContent = `${percent}%`;
        });
        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 400) {
                uploadProgressBar.style.width = '100%';
                uploadPercent.textContent = '100%';
                document.getElementById('upload-progress-text').textContent = 'Upload complete';
                window.setTimeout(() => window.location.reload(), 350);
                return;
            }
            uploadError.textContent = 'Upload failed. Please check the file and try again.';
            uploadError.hidden = false;
            uploadSubmit.disabled = false;
            uploadSubmit.textContent = 'Upload photo';
        });
        request.addEventListener('error', () => {
            uploadError.textContent = 'Upload failed because of a network error. Please try again.';
            uploadError.hidden = false;
            uploadSubmit.disabled = false;
            uploadSubmit.textContent = 'Upload photo';
        });
        request.open('POST', uploadForm.action);
        request.send(formData);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mapModal && !mapModal.hidden) closeMapModal();
        if (event.key === 'Escape') closePhotoModals();
    });
</script>
<style>
    /* Summary: one line of small chips (scrolls sideways on very narrow phones instead of wrapping) */
    .location-summary { display:flex; flex-wrap:nowrap; gap:6px; padding:12px 16px; overflow-x:auto; scrollbar-width:none; border-bottom:1px solid var(--line); }
    .location-summary::-webkit-scrollbar { display:none; }
    .location-chip { display:inline-flex; align-items:center; gap:6px; flex:0 0 auto; padding:4px 10px 4px 8px; border:1px solid var(--line); border-radius:999px; background:var(--surface-2); font-size:11.5px; white-space:nowrap; }
    .location-chip > div { display:inline-flex; align-items:baseline; gap:4px; }
    .location-chip .chip-dot { width:7px; height:7px; flex:0 0 7px; border-radius:50%; background:var(--primary); }
    .location-chip strong { font-size:12.5px; font-weight:800; color:var(--ink); font-variant-numeric:tabular-nums; }
    .location-chip small { font-size:11.5px; color:var(--muted); font-weight:600; }
    .location-chip.added { background:#e9f8ef; border-color:#c9ecd6; } .location-chip.added .chip-dot { background:#20a464; }
    .location-chip.missing { background:#fff6e0; border-color:#f6dfa6; } .location-chip.missing .chip-dot { background:#e09520; }
    [data-theme="dark"] .location-chip.added { background:#123222; border-color:#1d4a32; }
    [data-theme="dark"] .location-chip.missing { background:#3a2e10; border-color:#5a4718; }
    @media (max-width:650px) {
        .location-summary { padding:10px 13px; gap:5px; }
        .location-chip { padding:3px 8px 3px 7px; font-size:11px; }
        .location-chip strong { font-size:12px; } .location-chip small { font-size:11px; }
        .location-chip .chip-extra { display:none; }
    }

    /* ---------- Directory UI ---------- */
    .loc-flash { display:flex; align-items:center; gap:9px; margin-bottom:12px; padding:10px 13px; border-radius:11px; background:#e9f8ef; color:#146c3b; font-size:12.5px; font-weight:600; }
    .loc-flash svg { width:16px; height:16px; flex:0 0 16px; }
    [data-theme="dark"] .loc-flash { background:#123222; color:#69d998; }
    .loc-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 16px; border-bottom:1px solid var(--line); flex-wrap:wrap; }
    .loc-title h2 { margin:0; font-size:14px; } .loc-title .sub { margin-top:1px; }
    .loc-search { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .loc-search-box { position:relative; display:flex; align-items:center; }
    .loc-search-box svg { position:absolute; left:10px; width:15px; height:15px; color:var(--muted); pointer-events:none; }
    .loc-search-box input, .loc-select { padding:7px 11px; border:1px solid var(--line); border-radius:9px; background:var(--surface); color:var(--ink); font-size:12.5px; }
    .loc-search-box input { width:230px; padding-left:32px; }
    .loc-select { max-width:220px; appearance:none; padding-right:30px; cursor:pointer; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238592a8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 9px center; background-size:14px; }
    .loc-search-box input:focus, .loc-select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px #2563eb22; }
    .loc-btn { display:inline-flex; align-items:center; padding:7px 13px; border:1px solid var(--primary); border-radius:9px; background:var(--primary); color:#fff; font-size:12px; font-weight:700; cursor:pointer; text-decoration:none; white-space:nowrap; }
    .loc-btn:hover { filter:brightness(1.08); }
    .loc-btn.ghost { background:var(--surface); color:var(--ink); border-color:var(--line); } .loc-btn.ghost:hover { border-color:var(--primary); color:var(--primary); filter:none; }

    a.location-chip { text-decoration:none; transition:border-color .15s, box-shadow .15s; }
    a.location-chip:hover { border-color:var(--primary); }
    .location-chip.on { box-shadow:0 0 0 2px var(--primary); border-color:transparent; }
    .photo-progress { display:inline-flex; align-items:center; gap:7px; margin-left:auto; padding-left:8px; color:var(--muted); font-size:11px; font-weight:600; white-space:nowrap; }
    .photo-progress .bar { width:90px; height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
    .photo-progress .bar i { display:block; height:100%; border-radius:3px; background:#20a464; }

    .loc-table td { vertical-align:middle; }
    .loc-table td.idx { color:var(--muted); font-size:11.5px; width:38px; }
    .loc-name { display:flex; align-items:center; gap:10px; }
    .loc-avatar { display:grid; place-items:center; width:30px; height:30px; flex:0 0 30px; border-radius:9px; color:#fff; font-weight:800; font-size:12px; }
    .loc-name strong { display:block; font-size:12.5px; } .loc-name small { display:block; color:var(--muted); font-size:11.5px; }
    .loc-name small.missing-bn { color:#c27c0e; font-style:italic; }
    .parent-link { color:var(--ink); font-weight:600; text-decoration:none; } .parent-link:hover { color:var(--primary); text-decoration:underline; }
    .parent-sub { display:block; color:var(--muted); font-size:11px; }
    .code-pill { display:inline-block; padding:2px 8px; border-radius:6px; background:var(--surface-2); border:1px solid var(--line); font:600 11px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--ink); }
    .child-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; background:var(--primary-soft); color:var(--primary); font-size:11.5px; font-weight:700; text-decoration:none; white-space:nowrap; }
    .child-pill:hover { filter:brightness(.96); } .child-pill span { transition:transform .15s; } .child-pill:hover span { transform:translateX(2px); }
    .map-missing { gap:5px; padding:5px 10px; border-radius:8px; font-size:11.5px; }
    .map-missing svg { width:12px; height:12px; }
    .map-missing:hover { border-color:var(--primary); background:var(--primary-soft); }
    .location-map-thumb { width:56px; height:40px; }
    .act-cell { text-align:right; }
    .act-edit { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border:1px solid var(--line); border-radius:8px; color:var(--ink); font-size:12px; font-weight:700; text-decoration:none; transition:.15s; }
    .act-edit svg { width:14px; height:14px; } .act-edit:hover { border-color:var(--primary); color:var(--primary); }
    .loc-empty { display:grid; place-items:center; gap:5px; padding:40px 16px; text-align:center; color:var(--muted); font-size:12.5px; }
    .loc-empty .ico { display:grid; place-items:center; width:46px; height:46px; border-radius:14px; background:var(--primary-soft); color:var(--primary); margin-bottom:4px; }
    .loc-empty .ico svg { width:22px; height:22px; } .loc-empty strong { color:var(--ink); font-size:14px; }

    @media (max-width:860px) { .loc-toolbar { flex-direction:column; align-items:stretch; } .loc-search-box { flex:1 1 200px; } .loc-search-box input { width:100%; } }
    @media (max-width:650px) {
        .loc-toolbar { padding:12px; }
        .loc-search { display:grid; grid-template-columns:1fr auto; }
        .loc-search-box { grid-column:1 / -1; }
        .loc-select { max-width:none; width:100%; }
        .loc-search .loc-btn { justify-content:center; }
        .loc-search:has(.loc-select) .loc-btn:not(.ghost) { grid-column:auto; }
        .photo-progress { display:none; }
        .table-wrap.stacked td[data-label="NAME"]::before { content:none; }
        .table-wrap.stacked td[data-label="NAME"] { justify-content:flex-start; text-align:left; }
        .table-wrap.stacked td[data-label=""] { justify-content:flex-end; }
        .table-wrap.stacked td[data-label="PARENT"] { justify-content:flex-end; gap:5px; }
        .table-wrap.stacked td[data-label="PARENT"]::before { margin-right:auto; }
        .table-wrap.stacked td[data-label="PARENT"] .parent-sub { display:inline; font-size:12px; }
        .table-wrap.stacked td[data-label="PARENT"] .parent-sub::before { content:"· "; }
    }
</style>
<script nonce="{{ Vite::cspNonce() }}">
    // Changing the division / district filter applies it immediately.
    document.querySelector('[data-autosubmit]')?.addEventListener('change', (event) => event.target.form.requestSubmit());
</script>
@endsection
