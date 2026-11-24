@extends('layouts.app')

@php
    $editing = (bool) $locationSet;
    $status = old('status', $locationSet?->status ?? 'active');
    $selDivision = (string) old('division_id', $locationSet?->division_id);
    $selDistrict = (string) old('district_id', $locationSet?->district_id);
    $selUpazila = (string) old('upazila_id', $locationSet?->upazila_id);
    $remarks = old('remarks', $locationSet?->remarks);
    $photoRequired = ! $editing || ! $locationSet->image_path;
    $missingMaps = (int) ($missingDivisionImages ?? 0);
    $invalid = fn (string $field) => $errors->has($field) ? 'true' : 'false';
    $label = fn ($m) => $m->name_bn ? $m->name.' · '.$m->name_bn : $m->name;

    // Compact lookup data for the cascading selects (rebuilt client-side; works on iOS Safari).
    $locationData = [
        'districts' => $districts->map(fn ($d) => [$d->id, $d->division_id, $label($d)])->values(),
        'upazilas' => $upazilas->map(fn ($u) => [$u->id, $u->district_id, $label($u)])->values(),
        'divisionNames' => $divisions->mapWithKeys(fn ($d) => [$d->id => $label($d)]),
    ];
@endphp

@push('head')
<style>
    .loc-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px 16px; }
    .path { display:grid; gap:0; margin:0; padding:0; list-style:none; }
    .path li { position:relative; display:grid; grid-template-columns:22px 1fr; gap:10px; padding-bottom:14px; }
    .path li:last-child { padding-bottom:0; }
    .path li::before { content:""; position:absolute; left:10px; top:22px; bottom:2px; width:2px; border-radius:1px; background:var(--line); }
    .path li:last-child::before { display:none; }
    .path .node { display:grid; place-items:center; width:22px; height:22px; border-radius:50%; border:2px solid var(--line); background:var(--surface); color:transparent; font-size:11px; font-weight:800; transition:.2s; }
    .path li.done .node { border-color:var(--primary); background:var(--primary); color:#fff; }
    .path li.done::before { background:var(--primary); }
    .path small { display:block; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.6px; font-weight:700; }
    .path span.val { display:block; font-size:13px; font-weight:600; line-height:1.35; }
    .path li:not(.done) span.val { color:var(--muted); font-weight:400; }
    .preview-image { width:100%; aspect-ratio:16/10; object-fit:cover; border-radius:10px; background:var(--surface-2); display:block; }
    .preview-status { display:inline-flex; align-items:center; gap:6px; margin-top:12px; padding:3px 10px; border-radius:20px; background:var(--surface-2); font-size:11.5px; font-weight:700; color:var(--muted); }
    @media (max-width:760px) { .loc-grid { grid-template-columns:minmax(0,1fr); } }
</style>
@endpush

@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div>
            <a class="page-back" href="{{ route('location-sets.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 6-6 6 6 6"/></svg>Location sets</a>
            <h1>{{ $editing ? 'Edit location set' : 'Add location set' }}</h1>
            <p class="sub">Pick a division, district and thana, then add status, remarks and a photo.</p>
        </div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>

@if($missingMaps > 0)
    <div class="alert-error" role="status" style="background:#fff6e0;color:#8a5a00">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17v.5"/></svg>
        <div><strong>{{ $missingMaps }} {{ \Illuminate\Support\Str::plural('division', $missingMaps) }} still need a map image.</strong> Location sets can only be saved after every division has one. <a href="{{ route('locations.divisions') }}" style="color:inherit;font-weight:700">Upload division maps →</a></div>
    </div>
@endif

@if($errors->any())
    <div class="alert-error" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>
        <div><strong>Please fix {{ $errors->count() === 1 ? 'this problem' : 'these '.$errors->count().' problems' }}:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    </div>
@endif

<form method="post" action="{{ $editing ? route('location-sets.update', $locationSet) : route('location-sets.store') }}" enctype="multipart/form-data" class="form-layout" data-submit-once>
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="form-card">
        {{-- 1. Location --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">1</span><div><h2>Location</h2><p>Each list narrows down the next one.</p></div></div>
            <div class="loc-grid">
                <div class="field">
                    <label class="field-label" for="division_id">Division <span class="req">*</span></label>
                    <select class="input" id="division_id" name="division_id" required aria-invalid="{{ $invalid('division_id') }}">
                        <option value="">Select division</option>
                        @foreach($divisions as $division)<option value="{{ $division->id }}" @selected($selDivision === (string) $division->id)>{{ $label($division) }}</option>@endforeach
                    </select>
                    @error('division_id')<span class="field-error">{{ $message }}</span>@else<span class="hint">{{ $divisions->count() }} divisions</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="district_id">District <span class="req">*</span></label>
                    <select class="input" id="district_id" name="district_id" required aria-invalid="{{ $invalid('district_id') }}">
                        <option value="">Select district</option>
                        @foreach($districts as $district)<option value="{{ $district->id }}" @selected($selDistrict === (string) $district->id)>{{ $label($district) }}</option>@endforeach
                    </select>
                    @error('district_id')<span class="field-error">{{ $message }}</span>@else<span class="hint" data-count="district">Choose a division first</span>@enderror
                </div>
                <div class="field">
                    <label class="field-label" for="upazila_id">Thana / Upazila <span class="req">*</span></label>
                    <select class="input" id="upazila_id" name="upazila_id" required aria-invalid="{{ $invalid('upazila_id') }}">
                        <option value="">Select thana</option>
                        @foreach($upazilas as $upazila)<option value="{{ $upazila->id }}" @selected($selUpazila === (string) $upazila->id)>{{ $label($upazila) }}</option>@endforeach
                    </select>
                    @error('upazila_id')<span class="field-error">{{ $message }}</span>@else<span class="hint" data-count="upazila">Choose a district first</span>@enderror
                </div>
            </div>
        </section>

        {{-- 2. Details --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">2</span><div><h2>Details</h2><p>Status and any notes for your team.</p></div></div>
            <div class="form-grid">
                <div class="field span-2" style="max-width:340px">
                    <span class="field-label" id="status-label">Status <span class="req">*</span></span>
                    <div class="segmented" role="radiogroup" aria-labelledby="status-label">
                        @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $text)
                            <label><input type="radio" name="status" value="{{ $value }}" @checked($status === $value) required><span><i class="dot {{ $value }}"></i>{{ $text }}</span></label>
                        @endforeach
                    </div>
                    @error('status')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field span-2">
                    <label class="field-label" for="remarks">Remarks <span class="opt">optional</span></label>
                    <textarea class="input" id="remarks" name="remarks" rows="4" maxlength="5000" placeholder="Anything the team should know about this location…" aria-invalid="{{ $invalid('remarks') }}">{{ $remarks }}</textarea>
                    <div class="field-foot">@error('remarks')<span class="field-error">{{ $message }}</span>@else<span></span>@enderror<span class="hint" data-counter>{{ mb_strlen((string) $remarks) }} / 5000</span></div>
                </div>
            </div>
        </section>

        {{-- 3. Photo --}}
        <section class="form-section">
            <div class="form-section-head"><span class="step">3</span><div><h2>Photo @if($photoRequired)<span class="req">*</span>@else<span class="opt">optional</span>@endif</h2><p>JPG, PNG or WEBP, up to 100 MB. Large photos are auto-compressed to under 500 KB.</p></div></div>
            <div class="field">
                <div class="file-preview" data-file-preview @if(!$editing || !$locationSet->image_path) hidden @endif>
                    <img src="{{ $editing && $locationSet->image_path ? route('location-sets.image', $locationSet) : '' }}" alt="Selected photo" data-file-thumb>
                    <div><strong data-file-name>{{ $editing && $locationSet->image_path ? 'Current photo' : '' }}</strong><span data-file-meta>{{ $editing && $locationSet->image_path ? 'Upload a new file to replace it' : '' }}</span></div>
                    <button type="button" class="btn" data-file-clear hidden>Remove</button>
                </div>
                <label class="dropzone" data-dropzone>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-invalid="{{ $invalid('image') }}" data-file-input @if($photoRequired) required @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    <span><strong>Click to upload</strong> or drag a photo here</span>
                    <span class="hint">{{ $editing && $locationSet->image_path ? 'This will replace the current photo' : 'Up to 100 MB (auto-compressed to under 500 KB)' }}</span>
                </label>
                @error('image')<span class="field-error">{{ $message }}</span>@enderror
                <span class="field-error" data-file-error hidden></span>
            </div>
        </section>

        <div class="form-actions">
            <span class="hint"><span class="req">*</span> Required fields</span>
            <a class="btn" href="{{ route('location-sets.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit" data-loading-text="Saving…">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                <span data-label>{{ $editing ? 'Save changes' : 'Add location set' }}</span>
            </button>
        </div>
    </div>

    <aside class="side-stack">
        <div class="form-card side-card" aria-live="polite">
            <h3>Summary</h3>
            <ol class="path">
                <li data-path="division"><span class="node">✓</span><div><small>Division</small><span class="val">Not selected</span></div></li>
                <li data-path="district"><span class="node">✓</span><div><small>District</small><span class="val">Not selected</span></div></li>
                <li data-path="upazila"><span class="node">✓</span><div><small>Thana / Upazila</small><span class="val">Not selected</span></div></li>
            </ol>
            <span class="preview-status"><i class="dot {{ $status }}" data-status-dot></i><span data-status-text>{{ ucfirst($status) }}</span></span>
        </div>
        <div class="form-card side-card tips-card">
            <h3>Good to know</h3>
            <ul class="tips">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>Only thanas inside the chosen district are shown, so the location is always valid.</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8"/></svg>A clear landscape photo looks best in the list preview.</li>
            </ul>
        </div>
    </aside>
</form>

<script type="application/json" id="location-data">{!! json_encode($locationData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(() => {
    const data = JSON.parse(document.getElementById('location-data').textContent);
    const division = document.getElementById('division_id');
    const district = document.getElementById('district_id');
    const upazila = document.getElementById('upazila_id');
    const countHint = (key) => document.querySelector(`[data-count="${key}"]`);

    const fill = (select, rows, parentId, placeholder, keep) => {
        const items = parentId ? rows.filter((r) => String(r[1]) === String(parentId)) : [];
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach(([id, , name]) => select.add(new Option(name, id, false, String(id) === String(keep))));
        select.disabled = !parentId;
        return items.length;
    };

    const syncDistricts = (keep) => {
        const n = fill(district, data.districts, division.value, 'Select district', keep);
        const hint = countHint('district');
        if (hint) hint.textContent = division.value ? `${n} districts in this division` : 'Choose a division first';
    };
    const syncUpazilas = (keep) => {
        const n = fill(upazila, data.upazilas, district.value, 'Select thana', keep);
        const hint = countHint('upazila');
        if (hint) hint.textContent = district.value ? `${n} thanas in this district` : 'Choose a district first';
    };

    const selectedText = (select) => select.value ? select.options[select.selectedIndex].text : null;
    const updateSummary = () => {
        [['division', division], ['district', district], ['upazila', upazila]].forEach(([key, select]) => {
            const li = document.querySelector(`[data-path="${key}"]`);
            const text = selectedText(select);
            li.classList.toggle('done', !!text);
            li.querySelector('.val').textContent = text || 'Not selected';
        });
    };

    // Initial state keeps server-side selections (old input / edit values).
    const initialDistrict = district.value, initialUpazila = upazila.value;
    syncDistricts(initialDistrict);
    syncUpazilas(initialUpazila);
    updateSummary();

    division.addEventListener('change', () => { syncDistricts(); syncUpazilas(); updateSummary(); district.focus(); });
    district.addEventListener('change', () => { syncUpazilas(); updateSummary(); upazila.focus(); });
    upazila.addEventListener('change', updateSummary);

    // Status summary
    document.querySelectorAll('input[name="status"]').forEach((radio) => radio.addEventListener('change', () => {
        document.querySelector('[data-status-dot]').className = 'dot ' + radio.value;
        document.querySelector('[data-status-text]').textContent = radio.value.charAt(0).toUpperCase() + radio.value.slice(1);
    }));

    // Remarks counter
    const remarks = document.getElementById('remarks'), counter = document.querySelector('[data-counter]');
    remarks.addEventListener('input', () => { counter.textContent = `${remarks.value.length} / 5000`; });

    // Photo: drag & drop, client-side type/size check, instant preview
    const input = document.querySelector('[data-file-input]');
    const zone = document.querySelector('[data-dropzone]');
    const preview = document.querySelector('[data-file-preview]');
    const thumb = document.querySelector('[data-file-thumb]');
    const fileName = document.querySelector('[data-file-name]');
    const fileMeta = document.querySelector('[data-file-meta]');
    const clear = document.querySelector('[data-file-clear]');
    const error = document.querySelector('[data-file-error]');
    const original = { show: !preview.hidden, src: thumb.getAttribute('src'), name: fileName.textContent, meta: fileMeta.textContent };
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    const maxBytes = 100 * 1024 * 1024;
    let objectUrl = null;

    const reset = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        input.value = '';
        preview.hidden = !original.show;
        thumb.src = original.src || '';
        fileName.textContent = original.name;
        fileMeta.textContent = original.meta;
        clear.hidden = true;
    };
    const showError = (msg) => { error.textContent = msg; error.hidden = !msg; };

    input.addEventListener('change', () => {
        const file = input.files[0];
        showError('');
        if (!file) return reset();
        if (!allowed.includes(file.type)) { reset(); return showError('Please choose a JPG, PNG or WEBP image.'); }
        if (file.size > maxBytes) { reset(); return showError(`This photo is ${(file.size / 1048576).toFixed(1)} MB. The limit is 100 MB.`); }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        thumb.src = objectUrl;
        fileName.textContent = file.name;
        fileMeta.textContent = `${(file.size / 1024).toFixed(0)} KB · ready to upload`;
        preview.hidden = false;
        clear.hidden = false;
    });
    clear.addEventListener('click', () => { reset(); showError(''); });
    ['dragenter', 'dragover'].forEach((t) => zone.addEventListener(t, () => zone.classList.add('drag')));
    ['dragleave', 'drop'].forEach((t) => zone.addEventListener(t, () => zone.classList.remove('drag')));
})();
</script>
@endpush
