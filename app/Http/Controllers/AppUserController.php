<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\ProfilePhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AppUserController extends Controller
{
    private const STATUSES = ['active', 'inactive', 'blocked'];
    private const ACCOUNT_TYPES = ['free', 'paid', 'trial'];
    private const PACKAGE_STATUSES = ['pending', 'active', 'expired', 'cancelled'];

    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;

        // Escape LIKE wildcards so "%" or "_" typed by the admin are matched literally.
        $like = '%'.addcslashes($search, '%_\\').'%';
        // "#1143" or "1143" also matches the user ID exactly.
        $idSearch = preg_match('/^#?(\d{1,18})$/', $search, $m) ? (int) $m[1] : null;
        $searchScope = fn ($query) => $query->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
            ->where('name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->when($idSearch, fn ($q) => $q->orWhere('id', $idSearch))));

        // One grouped query for the filter chips (counts respect the search, not the status filter).
        $counts = AppUser::query()->tap($searchScope)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v): int => (int) $v);

        return view('app-users.index', [
            'users' => AppUser::query()
                ->tap($searchScope)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->withCount('logs')
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'counts' => [
                'all' => $counts->sum(),
                'active' => $counts->get('active', 0),
                'inactive' => $counts->get('inactive', 0),
                'blocked' => $counts->get('blocked', 0),
            ],
        ]);
    }

    public function create(): View
    {
        return view('app-users.form', ['appUser' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $appUser = new AppUser($data);

        if ($request->hasFile('photo')) {
            $appUser->photo_path = ProfilePhoto::store($request->file('photo'));
        }

        $appUser->save();

        return redirect()->route('app-users.index')->with('success', 'App user created successfully.');
    }

    public function edit(AppUser $appUser): View
    {
        return view('app-users.form', ['appUser' => $appUser->loadCount('logs')]);
    }

    public function update(Request $request, AppUser $appUser): RedirectResponse
    {
        $appUser->fill($this->validated($request, false, $appUser));
        $oldPhoto = $appUser->photo_path;

        if ($request->hasFile('photo')) {
            $appUser->photo_path = ProfilePhoto::store($request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            $appUser->photo_path = null;
        }

        $appUser->save();

        if ($oldPhoto && $oldPhoto !== $appUser->photo_path) {
            ProfilePhoto::delete($oldPhoto);
        }

        return redirect()->route('app-users.index')->with('success', 'App user updated successfully.');
    }

    public function destroy(AppUser $appUser): RedirectResponse
    {
        $photo = $appUser->photo_path;
        \App\Models\DeletionLog::record('app_user', $appUser, $appUser->name.' <'.$appUser->email.'>');
        $appUser->delete();
        ProfilePhoto::delete($photo);

        return back()->with('success', 'App user deleted successfully.');
    }

    /** Streams a profile photo from the private disk (admins only, via the auth route group). */
    public function photo(AppUser $appUser): StreamedResponse
    {
        abort_unless($appUser->photo_path && Storage::disk(ProfilePhoto::DISK)->exists($appUser->photo_path), 404);

        return Storage::disk(ProfilePhoto::DISK)->response($appUser->photo_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function logs(AppUser $appUser): View
    {
        return view('app-users.logs', [
            'appUser' => $appUser,
            'logs' => $appUser->logs()->latest()->get(),
        ]);
    }

    public function startTrial(AppUser $appUser): RedirectResponse
    {
        if ($appUser->trialAlreadyUsed()) {
            return back()->with('error', 'This user has already used the 14-day trial.');
        }
        if ($appUser->hasActiveSubscription()) {
            return back()->with('error', 'This user already has an active subscription.');
        }

        $start = Carbon::today();
        $appUser->forceFill([
            'account_type' => 'trial',
            'subscription_id' => 'TRIAL-'.str()->upper(str()->random(20)),
            'subscription_package_name' => '14 Day Free Trial',
            'subscription_package_id' => 'trial-14-days',
            'subscription_package_type' => 'trial',
            'package_start_date' => $start,
            'package_end_date' => $start->copy()->addDays(13),
            'package_status' => 'active',
            'package_auto_renew' => false,
            'trial_started_at' => $start,
            'trial_ends_at' => $start->copy()->addDays(13),
        ])->save();

        return back()->with('success', '14-day trial assigned successfully.');
    }

    private function validated(Request $request, bool $passwordRequired, ?AppUser $appUser = null): array
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => preg_replace('/[\s-]+/', '', trim((string) $request->input('phone'))) ?: null,
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('app_users', 'email')->ignore($appUser?->id)],
            'phone' => ['nullable', 'string', 'regex:/^01[3-9][0-9]{8}$/', Rule::unique('app_users', 'phone')->ignore($appUser?->id)],
            'pin' => [$passwordRequired ? 'required' : 'nullable', 'digits:6'],
            'status' => ['required', 'in:active,inactive,blocked'],
            'account_type' => ['required', Rule::in(self::ACCOUNT_TYPES)],
            'subscription_id' => ['nullable', 'string', 'max:190'],
            'subscription_package_name' => ['nullable', 'string', 'max:120'],
            'subscription_package_id' => ['nullable', 'string', 'max:100'],
            'subscription_package_type' => ['nullable', 'string', 'max:50'],
            'package_start_date' => ['nullable', 'date'],
            'package_end_date' => ['nullable', 'date', 'after_or_equal:package_start_date'],
            'package_status' => ['nullable', Rule::in(self::PACKAGE_STATUSES)],
            'package_auto_renew' => ['nullable', 'boolean'],
            'fcm_token' => ['nullable', 'string', 'max:5000'],
            'app_version' => ['nullable', 'string', 'max:30'],
            'platform' => ['nullable', 'string', 'max:30'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'photo' => ProfilePhoto::validationRules(),
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'photo.image' => 'This file is not a valid photo. Please choose a JPG or PNG image.',
            'photo.max' => 'The photo must be smaller than '.(ProfilePhoto::MAX_KB / 1024).' MB.',
            'photo.mimes' => 'The photo must be a '.strtoupper(implode(', ', ProfilePhoto::acceptedExtensions())).' image.',
            'photo.dimensions' => 'The photo must be at least 64×64 and at most '.ProfilePhoto::MAX_SOURCE_PX.' pixels on each side.',
        ]);

        unset($data['photo'], $data['remove_photo']);

        if (blank($data['pin'] ?? null)) {
            unset($data['pin']);
        } else {
            $data['password'] = $data['pin'];
            unset($data['pin']);
        }

        return $data;
    }
}
