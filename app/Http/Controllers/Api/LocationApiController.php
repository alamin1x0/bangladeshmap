<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationApiController extends Controller
{
    public function divisions(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($denied = $this->subscriptionDenied($user)) {
            return $denied;
        }

        $divisions = Division::query()
            ->withCount(['districts', 'upazilas'])
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn', 'code', 'image_path'])
            ->map(fn (Division $division): array => [
                'id' => $division->id,
                'name' => $division->name,
                'name_bn' => $division->name_bn,
                'code' => $division->code,
                'district_count' => $division->districts_count,
                'upazila_count' => $division->upazilas_count,
                'has_map' => filled($division->image_path),
                'image_url' => $division->image_path ? route('api.divisions.map', $division->id) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'account_type' => strtoupper($user->account_type),
                'divisions' => $divisions,
                'total' => $divisions->count(),
            ],
        ]);
    }

    public function districts(Request $request): JsonResponse
    {
        if ($denied = $this->subscriptionDenied($request->user())) {
            return $denied;
        }

        $data = $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
        ]);
        $districts = District::query()
            ->where('division_id', $data['division_id'])
            ->withCount('upazilas')
            ->orderBy('name')
            ->get(['id', 'division_id', 'name', 'name_bn', 'code', 'image_path'])
            ->map(fn (District $district): array => [
                'id' => $district->id,
                'division_id' => $district->division_id,
                'name' => $district->name,
                'name_bn' => $district->name_bn,
                'code' => $district->code,
                'upazila_count' => $district->upazilas_count,
                'has_map' => filled($district->image_path),
                'image_url' => $district->image_path ? route('api.districts.map', $district->id) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'division_id' => (int) $data['division_id'],
                'districts' => $districts,
                'total' => $districts->count(),
            ],
        ]);
    }

    public function upazilas(Request $request): JsonResponse
    {
        if ($denied = $this->subscriptionDenied($request->user())) {
            return $denied;
        }

        $data = $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);
        $upazilas = Upazila::query()
            ->where('district_id', $data['district_id'])
            ->orderBy('name')
            ->get(['id', 'district_id', 'name', 'name_bn', 'code', 'type', 'image_path'])
            ->map(fn (Upazila $upazila): array => [
                'id' => $upazila->id,
                'district_id' => $upazila->district_id,
                'name' => $upazila->name,
                'name_bn' => $upazila->name_bn,
                'code' => $upazila->code,
                'type' => $upazila->type,
                'has_map' => filled($upazila->image_path),
                'image_url' => $upazila->image_path ? route('api.upazilas.map', $upazila->id) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'district_id' => (int) $data['district_id'],
                'upazilas' => $upazilas,
                'total' => $upazilas->count(),
            ],
        ]);
    }

    public function mapImage(Request $request, int $id): StreamedResponse
    {
        $routeName = $request->route()->getName();
        $model = match ($routeName) {
            'api.divisions.map' => Division::findOrFail($id),
            'api.districts.map' => District::findOrFail($id),
            'api.upazilas.map' => Upazila::findOrFail($id),
            default => abort(404),
        };

        abort_unless($model->image_path && Storage::disk('public')->exists($model->image_path), 404);

        return Storage::disk('public')->response($model->image_path, null, [
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }

    private function subscriptionDenied($user): ?JsonResponse
    {
        if (! in_array($user->account_type, ['paid', 'trial'], true)) {
            return response()->json([
                'success' => false,
                'code' => 'SUBSCRIPTION_REQUIRED',
                'message' => 'This location list is available only for PAID or TRIAL accounts.',
            ], 403);
        }
        if (! $user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'code' => 'SUBSCRIPTION_EXPIRED',
                'message' => 'Your package is not active or its validity period has ended.',
            ], 403);
        }

        return null;
    }
}
