<?php

namespace App\Http\Controllers;

use App\Models\DeletionLog;
use App\Models\District;
use App\Models\Division;
use App\Models\LocationSet;
use App\Models\Upazila;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationSetController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->toString();

        return view('location-sets.index', [
            'items' => LocationSet::with(['division', 'district', 'upazila'])
                ->when($search, fn ($query) => $query->whereHas('division', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('name_bn', 'like', "%{$search}%"))
                    ->orWhereHas('district', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('name_bn', 'like', "%{$search}%"))
                    ->orWhereHas('upazila', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('name_bn', 'like', "%{$search}%")))
                ->latest()->get(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('location-sets.form', array_merge($this->formData(), [
            'missingDivisionImages' => Division::whereNull('image_path')->orWhere('image_path', '')->count(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['image_path'] = $request->hasFile('image') ? \App\Support\ImageCompressor::store($request->file('image'), 'location-sets') : null;
        LocationSet::create($data);

        return redirect()->route('location-sets.index')->with('success', 'Location set added successfully.');
    }

    public function edit(LocationSet $locationSet): View
    {
        return view('location-sets.form', array_merge($this->formData($locationSet), [
            'missingDivisionImages' => Division::whereNull('image_path')->orWhere('image_path', '')->count(),
        ]));
    }

    public function update(Request $request, LocationSet $locationSet): RedirectResponse
    {
        $data = $this->validated($request, ! $locationSet->image_path);

        if ($request->hasFile('image')) {
            if ($locationSet->image_path) {
                Storage::disk('public')->delete($locationSet->image_path);
            }
            $data['image_path'] = \App\Support\ImageCompressor::store($request->file('image'), 'location-sets');
        }

        $locationSet->update($data);

        return redirect()->route('location-sets.index')->with('success', 'Location set updated successfully.');
    }

    public function destroy(LocationSet $locationSet): RedirectResponse
    {
        if ($locationSet->image_path) {
            Storage::disk('public')->delete($locationSet->image_path);
        }
        $locationSet->loadMissing(['division', 'district', 'upazila']);
        DeletionLog::record('location_set', $locationSet, implode(' / ', array_filter([$locationSet->division?->name, $locationSet->district?->name, $locationSet->upazila?->name])) ?: 'Set #'.$locationSet->id);
        $locationSet->delete();

        return back()->with('success', 'Location set deleted successfully.');
    }

    public function image(LocationSet $locationSet): StreamedResponse
    {
        abort_unless($locationSet->image_path && Storage::disk('public')->exists($locationSet->image_path), 404);

        return Storage::disk('public')->response($locationSet->image_path);
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'upazila_id' => ['required', 'integer', 'exists:upazilas,id'],
            'status' => ['required', 'in:active,inactive'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'image' => \App\Support\ImageCompressor::rules($imageRequired),
        ], [], [
            'division_id' => 'division',
            'district_id' => 'district',
            'upazila_id' => 'thana / upazila',
            'image' => 'photo',
        ]);

        // Business rules are reported as normal form errors (user keeps their input) instead of a bare 422 page.
        if (Division::whereNull('image_path')->orWhere('image_path', '')->exists()) {
            throw ValidationException::withMessages([
                'division_id' => 'All division map images must be uploaded before creating a location set.',
            ]);
        }

        if (! Division::whereKey($data['division_id'])->whereNotNull('image_path')->where('image_path', '<>', '')->exists()) {
            throw ValidationException::withMessages([
                'division_id' => 'The selected division must have a map image before this location set can be saved.',
            ]);
        }

        if (! District::whereKey($data['district_id'])->where('division_id', $data['division_id'])->exists()) {
            throw ValidationException::withMessages(['district_id' => 'This district does not belong to the selected division.']);
        }

        if (! Upazila::whereKey($data['upazila_id'])->where('district_id', $data['district_id'])->exists()) {
            throw ValidationException::withMessages(['upazila_id' => 'This thana does not belong to the selected district.']);
        }

        unset($data['image']);
        return $data;
    }

    private function formData(?LocationSet $locationSet = null): array
    {
        return [
            'locationSet' => $locationSet,
            'divisions' => Division::orderBy('name')->get(['id', 'name', 'name_bn']),
            'districts' => District::orderBy('name')->get(['id', 'division_id', 'name', 'name_bn']),
            'upazilas' => Upazila::orderBy('name')->get(['id', 'district_id', 'name', 'name_bn']),
        ];
    }
}
