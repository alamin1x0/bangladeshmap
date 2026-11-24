<?php

namespace App\Http\Controllers;

use App\Models\DeletionLog;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationController extends Controller
{
    public function divisions(): View
    {
        $search = request('q');

        return view('locations.index', [
            'title' => 'Divisions',
            'type' => 'divisions',
            'search' => $search,
            'items' => Division::withCount('districts')
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('name_bn', 'like', "%{$search}%")))
                ->orderBy('name')->get(),
        ]);
    }

    public function districts(Request $request): View
    {
        $division = $request->integer('division');
        $search = $request->string('q')->toString();

        return view('locations.index', [
            'title' => 'Districts',
            'type' => 'districts',
            'search' => $search,
            'filter' => Division::orderBy('name')->get(),
            'selected' => $division,
            'items' => District::with('division')->withCount('upazilas')
                ->when($division, fn ($query) => $query->where('division_id', $division))
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('name_bn', 'like', "%{$search}%")))
                ->orderBy('name')->get(),
        ]);
    }

    public function upazilas(Request $request): View
    {
        $district = $request->integer('district');
        $search = $request->string('q')->toString();

        return view('locations.index', [
            'title' => 'Upazilas / Thanas',
            'type' => 'upazilas',
            'search' => $search,
            'filter' => District::with('division')->orderBy('name')->get(),
            'selected' => $district,
            'items' => Upazila::with('district.division')
                ->when($district, fn ($query) => $query->where('district_id', $district))
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('name_bn', 'like', "%{$search}%")))
                ->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, string $type): View
    {
        abort_unless(in_array($type, ['districts', 'upazilas'], true), 404);

        return view('locations.create', [
            'type' => $type,
            'title' => $type === 'districts' ? 'Add district' : 'Add upazila / thana',
            'divisions' => Division::orderBy('name')->get(),
            'districts' => $type === 'upazilas' ? District::with('division')->orderBy('name')->get() : collect(),
            'selectedDivision' => $request->integer('division'),
            'selectedDistrict' => $request->integer('district'),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['districts', 'upazilas'], true), 404);

        if ($type === 'districts') {
            $data = $request->validate([
                'division_id' => ['required', 'exists:divisions,id'],
                'name' => ['required', 'string', 'max:150', Rule::unique('districts', 'name')->where('division_id', $request->input('division_id'))],
                'name_bn' => ['nullable', 'string', 'max:150'],
            ]);
            $district = DB::transaction(fn () => $this->createDistrict($data['division_id'], $data['name'], $data['name_bn'] ?? null));

            return redirect()->route('locations.districts', ['division' => $district->division_id])
                ->with('success', "District {$district->name} added ({$district->code}).");
        }

        $newDistrict = $request->input('district_id') === 'new';
        $data = $request->validate([
            'district_id' => ['required', $newDistrict ? 'in:new' : 'exists:districts,id'],
            'new_division_id' => [$newDistrict ? 'required' : 'nullable', 'exists:divisions,id'],
            'new_district_name' => [$newDistrict ? 'required' : 'nullable', 'string', 'max:150'],
            'new_district_name_bn' => ['nullable', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
        ]);

        $upazila = DB::transaction(function () use ($data, $newDistrict) {
            if ($newDistrict) {
                // Reuse an existing district of the same name instead of failing on the unique index.
                $district = District::where('division_id', $data['new_division_id'])->where('name', $data['new_district_name'])->first()
                    ?? $this->createDistrict($data['new_division_id'], $data['new_district_name'], $data['new_district_name_bn'] ?? null);
            } else {
                $district = District::findOrFail($data['district_id']);
            }

            abort_if(
                Upazila::where('district_id', $district->id)->where('name', $data['name'])->exists(),
                422,
                "{$data['name']} already exists in {$district->name}."
            );

            return Upazila::create([
                'district_id' => $district->id,
                'name' => $data['name'],
                'name_bn' => $data['name_bn'] ?? null,
                'code' => $this->nextCode($district->code, Upazila::class),
            ]);
        });

        return redirect()->route('locations.upazilas', ['district' => $upazila->district_id])
            ->with('success', "Thana {$upazila->name} added ({$upazila->code}).");
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['districts', 'upazilas'], true), 404);

        if ($type === 'upazilas') {
            $upazila = Upazila::findOrFail($id);
            if (DB::table('location_sets')->where('upazila_id', $id)->exists()) {
                return back()->with('error', "{$upazila->name} is used in a location set. Remove it from the set first.");
            }
            DB::transaction(function () use ($upazila): void {
                $upazila->loadMissing('district.division');
                DeletionLog::record('upazila', $upazila, null, $upazila->district?->division?->name.' / '.$upazila->district?->name);
                $this->deleteImage($upazila);
                $upazila->delete();
            });

            return back()->with('success', "Thana {$upazila->name} deleted.");
        }

        $district = District::with('upazilas')->findOrFail($id);
        $upazilaIds = $district->upazilas->pluck('id');
        if (DB::table('location_sets')->where(fn ($q) => $q->where('district_id', $id)->orWhereIn('upazila_id', $upazilaIds))->exists()) {
            return back()->with('error', "{$district->name} (or one of its thanas) is used in a location set. Remove it from the set first.");
        }

        $count = $upazilaIds->count();
        DB::transaction(function () use ($district): void {
            $district->loadMissing('division');
            DeletionLog::record('district', $district, null, $district->division?->name,
                $district->upazilas->map(fn ($u) => $u->only(['id', 'name', 'name_bn', 'code', 'image_path']))->all());
            // Thanas first, then the district.
            foreach ($district->upazilas as $upazila) {
                $this->deleteImage($upazila);
                $upazila->delete();
            }
            $this->deleteImage($district);
            $district->delete();
        });

        return back()->with('success', "District {$district->name} deleted along with {$count} thana(s).");
    }

    private function createDistrict(int|string $divisionId, string $name, ?string $nameBn): District
    {
        $division = Division::findOrFail($divisionId);

        return District::create([
            'division_id' => $division->id,
            'name' => $name,
            'name_bn' => $nameBn,
            'code' => $this->nextCode($division->code, District::class),
        ]);
    }

    /** Next unused "<parent>-NN" code, e.g. D02-05-17. */
    private function nextCode(?string $parentCode, string $model): string
    {
        $parentCode ??= 'X';
        $next = $model::where('code', 'like', $parentCode.'-%')->count() + 1;
        do {
            $code = sprintf('%s-%02d', $parentCode, $next++);
        } while ($model::where('code', $code)->exists());

        return $code;
    }

    private function deleteImage($model): void
    {
        if ($model->image_path) {
            Storage::disk('public')->delete($model->image_path);
        }
    }

    public function edit(string $type, int $id): View
    {
        [$model, $title] = match ($type) {
            'divisions' => [Division::findOrFail($id), 'Edit division'],
            'districts' => [District::with('division')->findOrFail($id), 'Edit district'],
            'upazilas' => [Upazila::with('district.division')->findOrFail($id), 'Edit upazila / thana'],
            default => abort(404),
        };

        $parents = match ($type) {
            'districts' => Division::orderBy('name')->get(),
            'upazilas' => District::with('division')->orderBy('name')->get(),
            default => collect(),
        };
        $impact = match ($type) {
            'districts' => $model->upazilas()->count(),
            default => 0,
        };

        return view('locations.edit', compact('model', 'title', 'type', 'parents', 'impact'));
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        [$model, $parentColumn] = match ($type) {
            'divisions' => [Division::findOrFail($id), null],
            'districts' => [District::findOrFail($id), 'division_id'],
            'upazilas' => [Upazila::findOrFail($id), 'district_id'],
            default => abort(404),
        };

        $parentTable = $type === 'districts' ? 'divisions' : 'districts';
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'parent_id' => [$parentColumn ? 'sometimes' : 'nullable', 'exists:'.$parentTable.',id'],
            'image' => \App\Support\ImageCompressor::rules(),
        ]);

        // The uniqueness scope is the (possibly new) parent. The map-photo modal sends no parent, so keep the current one.
        $parentId = $parentColumn ? (int) ($validated['parent_id'] ?? $model->{$parentColumn}) : null;
        $duplicate = $model::where('name', $validated['name'])->whereKeyNot($model->getKey())
            ->when($parentColumn, fn ($q) => $q->where($parentColumn, $parentId))->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors(['name' => "\"{$validated['name']}\" already exists in the selected ".($type === 'districts' ? 'division' : 'district').'.']);
        }
        if ($parentColumn) {
            $validated[$parentColumn] = $parentId;
        }
        unset($validated['parent_id']);

        if ($request->hasFile('image')) {
            if ($model->image_path) {
                Storage::disk('public')->delete($model->image_path);
            }
            $validated['image_path'] = \App\Support\ImageCompressor::store($request->file('image'), 'location-maps');
        }
        unset($validated['image']);

        $model->update($validated);

        $filter = $parentColumn ? [($type === 'districts' ? 'division' : 'district') => $model->{$parentColumn}] : [];

        return redirect()->route("locations.{$type}", $filter)->with('success', "{$model->name} updated successfully.");
    }

    public function removeImage(string $type, int $id): RedirectResponse
    {
        $model = match ($type) {
            'divisions' => Division::findOrFail($id),
            'districts' => District::findOrFail($id),
            'upazilas' => Upazila::findOrFail($id),
            default => abort(404),
        };

        if ($model->image_path) {
            Storage::disk('public')->delete($model->image_path);
            $model->update(['image_path' => null]);
        }

        return redirect()->route("locations.{$type}")->with('success', $model->name.' map photo removed successfully.');
    }

    public function image(string $type, int $id): StreamedResponse
    {
        $model = match ($type) {
            'divisions' => Division::findOrFail($id),
            'districts' => District::findOrFail($id),
            'upazilas' => Upazila::findOrFail($id),
            default => abort(404),
        };

        abort_unless($model->image_path && Storage::disk('public')->exists($model->image_path), 404);

        return Storage::disk('public')->response($model->image_path);
    }
}
