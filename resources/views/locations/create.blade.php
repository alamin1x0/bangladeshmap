@extends('layouts.app')
@section('content')
@php $input = 'width:100%;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink)'; @endphp
<header class="topbar">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <button class="icon-btn menu-btn" data-menu-toggle aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div><p class="eyebrow">Bangladesh locations</p><h1>{{ $title }}</h1><p class="sub">The system code is generated automatically.</p></div>
    </div>
    <div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle dark mode"></button></div>
</header>
<section class="table-card" style="max-width:680px;padding:26px">
    <form method="post" action="{{ route('locations.store', $type) }}">
        @csrf
        @if($type === 'districts')
            <label class="field" for="division_id">Division</label>
            <select id="division_id" name="division_id" required style="{{ $input }}">
                <option value="">Select division</option>
                @foreach($divisions as $d)<option value="{{ $d->id }}" @selected((int) old('division_id', $selectedDivision) === $d->id)>{{ $d->name }}</option>@endforeach
            </select>
            @error('division_id')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
            <label class="field" for="name">District name</label>
        @else
            <label class="field" for="district_id">District</label>
            <select id="district_id" name="district_id" required style="{{ $input }}">
                <option value="">Select district</option>
                <option value="new" @selected(old('district_id') === 'new')>+ Add new district…</option>
                @foreach($districts as $d)<option value="{{ $d->id }}" @selected((string) old('district_id', $selectedDistrict ?: '') === (string) $d->id)>{{ $d->name }} · {{ $d->division->name }}</option>@endforeach
            </select>
            @error('district_id')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror

            <div id="new-district" style="display:none;margin-top:14px;padding:14px;border:1px dashed var(--line);border-radius:10px">
                <label class="field" for="new_division_id" style="margin-top:0">Division of the new district</label>
                <select id="new_division_id" name="new_division_id" style="{{ $input }}">
                    <option value="">Select division</option>
                    @foreach($divisions as $d)<option value="{{ $d->id }}" @selected((int) old('new_division_id') === $d->id)>{{ $d->name }}</option>@endforeach
                </select>
                @error('new_division_id')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
                <label class="field" for="new_district_name">New district name</label>
                <input id="new_district_name" name="new_district_name" value="{{ old('new_district_name') }}" style="{{ $input }}">
                @error('new_district_name')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
                <label class="field" for="new_district_name_bn">New district Bangla name <span style="color:var(--muted);font-weight:400">(optional)</span></label>
                <input id="new_district_name_bn" name="new_district_name_bn" value="{{ old('new_district_name_bn') }}" style="{{ $input }}">
            </div>
            <label class="field" for="name">Thana / upazila name</label>
        @endif
        <input id="name" name="name" value="{{ old('name') }}" required style="{{ $input }}">
        @error('name')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        <label class="field" for="name_bn">Bangla name <span style="color:var(--muted);font-weight:400">(optional)</span></label>
        <input id="name_bn" name="name_bn" value="{{ old('name_bn') }}" style="{{ $input }}">
        @error('name_bn')<div class="error" style="color:#e05252;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        <p class="sub">You can upload the map image from the Edit page after saving.</p>
        <div style="display:flex;gap:10px;margin-top:24px"><button class="logout" type="submit" style="background:var(--primary);border-color:var(--primary);color:white">Save</button><a class="logout" href="{{ route("locations.{$type}") }}" style="text-decoration:none">Cancel</a></div>
    </form>
</section>
@if($type === 'upazilas')
<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        var sel = document.getElementById('district_id'), box = document.getElementById('new-district');
        function sync() {
            var isNew = sel.value === 'new';
            box.style.display = isNew ? 'block' : 'none';
            document.getElementById('new_division_id').required = isNew;
            document.getElementById('new_district_name').required = isNew;
        }
        sel.addEventListener('change', sync); sync();
    })();
</script>
@endif
@endsection
