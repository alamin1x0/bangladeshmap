@extends('layouts.app')
@section('content')
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div><p class="eyebrow">Bangladesh locations</p><h1>{{ $title }}</h1><p class="sub">Update the location name without changing its hierarchy.</p></div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>
<section class="table-card" style="max-width:680px;padding:26px">
    <form method="post" action="{{ route('locations.update', [$type, $model->id]) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @if($type !== 'divisions')
            @php $parentId = $type === 'districts' ? $model->division_id : $model->district_id; @endphp
            <label class="field" for="parent_id" style="margin-top:0">{{ $type === 'districts' ? 'Division' : 'District' }} <span style="color:var(--muted);font-weight:400">(change to move it)</span></label>
            <select id="parent_id" name="parent_id" required style="width:100%;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink)">
                @foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((int) old('parent_id', $parentId) === $parent->id)>{{ $parent->name }}{{ $type === 'upazilas' ? ' · '.$parent->division->name : '' }}</option>@endforeach
            </select>
            @error('parent_id')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        @endif
        <label class="field" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $model->name) }}" required style="width:100%;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink)">
        @error('name')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        <label class="field" for="name_bn">Bangla name <span style="color:var(--muted);font-weight:400">(optional)</span></label>
        <input id="name_bn" name="name_bn" value="{{ old('name_bn', $model->name_bn) }}" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink)">
        @error('name_bn')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        <label class="field" for="code">System code <span style="color:var(--muted);font-weight:400">(read-only)</span></label>
        <input id="code" value="{{ $model->code }}" readonly aria-readonly="true" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface-2);color:var(--muted);cursor:not-allowed">
        <label class="field" for="image">Map / location image <span style="color:var(--muted);font-weight:400">(JPG, PNG, WEBP; up to 100MB, auto-compressed to under 500KB)</span></label>
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" style="width:100%;padding:10px 0;color:var(--muted)">
        @error('image')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        @if($model->image_path)
            <img src="{{ route('locations.image', [$type, $model->id]) }}" alt="Current location map" style="display:block;width:130px;height:90px;object-fit:cover;border-radius:10px;margin-top:8px">
        @endif
        <div style="display:flex;gap:10px;margin-top:24px"><button class="logout" type="submit" style="background:var(--primary);border-color:var(--primary);color:white">Save changes</button><a class="logout" href="{{ route("locations.{$type}") }}" style="text-decoration:none">Cancel</a></div>
    </form>
</section>
@if($type !== 'divisions')
<section class="table-card" style="max-width:680px;padding:22px 26px;margin-top:16px;border-color:#f3c9c6">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
        <div>
            <strong style="color:#d92d20">Delete this {{ $type === 'districts' ? 'district' : 'thana' }}</strong>
            <p class="sub" style="margin:4px 0 0">{{ $type === 'districts' ? ($impact ? "Its {$impact} thana(s) will be deleted first." : 'It has no thanas.') : 'Removes this thana and its map photo.' }} This cannot be undone.</p>
        </div>
        <button type="button" class="logout" style="color:#d92d20;border-color:#f3c9c6;cursor:pointer" data-delete-url="{{ route('locations.destroy', [$type, $model->id]) }}" data-delete-kind="{{ $type === 'districts' ? 'district' : 'thana' }}" data-delete-name="{{ $model->name }}" @if($impact) data-delete-impact="This will also delete {{ $impact }} thana(s) under it." @endif>Delete</button>
    </div>
</section>
@include('locations._delete-modal')
@endif
@endsection
