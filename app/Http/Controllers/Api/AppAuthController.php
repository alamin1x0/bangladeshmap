<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserLog;
use App\Models\SubscriptionPaymentRequest;
use App\Services\AppJwtService;
use App\Support\ProfilePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AppAuthController extends Controller
{
    public function register(Request $request, AppJwtService $jwt): JsonResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('app_users', 'email')],
            'phone' => ['nullable', 'string', 'regex:/^01[3-9][0-9]{8}$/', Rule::unique('app_users', 'phone')],
            'pin' => ['required', 'digits:6'],
            'device_id' => ['required', 'string', 'max:190'],
            'platform' => ['nullable', 'string', 'max:30'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'app_version' => ['nullable', 'string', 'max:30'],
            'fcm_token' => ['nullable', 'string', 'max:5000'],
        ]);
        $user = AppUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $this->normalizePhone($data['phone'] ?? null),
            'password' => Hash::make($data['pin']),
            'status' => 'active',
            'account_type' => 'free',
        ]);

        $token = $jwt->issue($user, $this->device($request, $data));
        $this->log($user, 'registered', $request, $data);

        return $this->authResponse($user->fresh(), $token, 201);
    }

    public function login(Request $request, AppJwtService $jwt): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'pin' => ['required', 'digits:6'],
            'device_id' => ['required', 'string', 'max:190'],
            'platform' => ['nullable', 'string', 'max:30'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'app_version' => ['nullable', 'string', 'max:30'],
            'fcm_token' => ['required', 'string', 'max:5000'],
        ]);
        $identifier = trim($data['identifier']);
        $emailIdentifier = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $phoneIdentifier = preg_match('/^01[3-9][0-9]{8}$/', $this->normalizePhone($identifier)) === 1;
        if (! $emailIdentifier && ! $phoneIdentifier) {
            throw ValidationException::withMessages([
                'identifier' => 'Enter a valid email address or Bangladesh mobile number (11 digits, starting with 013–019).',
            ]);
        }
        $user = $emailIdentifier
            ? AppUser::where('email', strtolower($identifier))->first()
            : AppUser::where('phone', $this->normalizePhone($identifier))->first();

        if (! $user || ! Hash::check($data['pin'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid login details.'], 401);
        }
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Only active users can login.',
            ], 403);
        }

        $token = $jwt->issue($user, $this->device($request, $data));
        $this->log($user, 'logged_in', $request, $data);

        return $this->authResponse($user->fresh(), $token);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
        ]);
        $email = strtolower(trim($data['email']));
        $user = AppUser::where('email', $email)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $email,
                'account_exists' => (bool) $user,
                'can_login' => (bool) $user && $user->status === 'active',
                'status' => $user?->status,
                'next' => $user && $user->status === 'active' ? 'login_with_pin' : 'register',
            ],
        ]);
    }

    public function refresh(Request $request, AppJwtService $jwt): JsonResponse
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string', 'min:64', 'max:200'],
        ]);

        try {
            $token = $jwt->refresh($data['refresh_token']);
        } catch (Throwable $exception) {
            $status = $exception->getCode() === 403 ? 403 : 401;

            return response()->json([
                'success' => false,
                'code' => $status === 403 ? 'ACCOUNT_INACTIVE' : 'INVALID_REFRESH_TOKEN',
                'message' => $exception->getMessage(),
            ], $status);
        }

        return $this->authResponse(AppUser::where('api_token_hash', hash('sha256', $token['access_token']))->firstOrFail(), $token);
    }

    public function subscriptionStatus(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['subscription' => $this->subscriptionData($request->user())],
        ]);
    }

    public function startTrial(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();
        if ($user->trialAlreadyUsed()) {
            return response()->json([
                'success' => false,
                'code' => 'TRIAL_ALREADY_USED',
                'message' => 'The 14-day trial has already been used for this account.',
                'data' => ['subscription' => $this->subscriptionData($user)],
            ], 409);
        }

        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'code' => 'SUBSCRIPTION_ALREADY_ACTIVE',
                'message' => 'An active subscription already exists on this account.',
                'data' => ['subscription' => $this->subscriptionData($user)],
            ], 409);
        }

        $start = Carbon::today();
        $end = $start->copy()->addDays(13);
        $user->forceFill([
            'account_type' => 'trial',
            'subscription_id' => 'TRIAL-'.Str::upper(Str::random(20)),
            'subscription_package_name' => '14 Day Free Trial',
            'subscription_package_id' => 'trial-14-days',
            'subscription_package_type' => 'trial',
            'package_start_date' => $start,
            'package_end_date' => $end,
            'package_status' => 'active',
            'package_auto_renew' => false,
            'trial_started_at' => $start,
            'trial_ends_at' => $end,
        ])->save();
        $this->log($user, 'trial_started', $request);

        return response()->json([
            'success' => true,
            'message' => 'Your 14-day trial has started.',
            'data' => ['subscription' => $this->subscriptionData($user->fresh())],
        ], 201);
    }

    public function submitPayment(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();
        $data = $request->validate([
            'customer_bkash_number' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'merchant_bkash_number' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'transaction_id' => ['required', 'string', 'max:100', Rule::unique('subscription_payment_requests', 'transaction_id')],
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'package_name' => ['required', 'string', 'max:120'],
            'package_id' => ['nullable', 'string', 'max:100'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);
        if ($user->hasActiveSubscription() && $user->account_type === 'paid') {
            return response()->json(['success' => false, 'code' => 'SUBSCRIPTION_ALREADY_ACTIVE', 'message' => 'An active paid subscription already exists.'], 409);
        }
        if ($user->paymentRequests()->where('status', 'pending')->exists()) {
            return response()->json(['success' => false, 'code' => 'PAYMENT_ALREADY_PENDING', 'message' => 'A payment request is already pending review.'], 409);
        }

        $payment = $user->paymentRequests()->create($data + [
            'gateway' => 'bkash',
            'duration_days' => $data['duration_days'] ?? 30,
            'status' => 'pending',
        ]);
        $this->log($user, 'payment_submitted', $request, $data);

        return response()->json([
            'success' => true,
            'message' => 'Payment submitted and is waiting for admin approval.',
            'data' => ['payment' => $payment],
        ], 201);
    }

    public function paymentStatus(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['payments' => $request->user()->paymentRequests()->latest()->get()],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['user' => $this->userData($request->user())]]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['profile' => $this->userData($request->user())]]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->merge([
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:150'],
            'email' => ['prohibited'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^01[3-9][0-9]{8}$/', Rule::unique('app_users', 'phone')->ignore($user->id)],
            'photo' => ProfilePhoto::validationRules(),
            'remove_photo' => ['sometimes', 'boolean'],
        ]);
        if ($request->hasFile('photo') && $request->boolean('remove_photo')) {
            throw ValidationException::withMessages(['photo' => 'Choose upload or remove photo, not both.']);
        }

        $oldPhoto = $user->photo_path;
        $user->fill(collect($data)->only(['name', 'phone'])->all());
        if ($request->hasFile('photo')) {
            $user->photo_path = ProfilePhoto::store($request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            $user->photo_path = null;
        }
        $user->save();
        if ($oldPhoto && $oldPhoto !== $user->photo_path) {
            ProfilePhoto::delete($oldPhoto);
        }
        $this->log($user, 'profile_updated', $request);

        return response()->json(['success' => true, 'message' => 'Profile updated successfully.', 'data' => [
            'profile' => $this->userData($user->fresh()),
        ]]);
    }

    public function profilePhoto(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->photo_path && Storage::disk(ProfilePhoto::DISK)->exists($user->photo_path), 404);

        return Storage::disk(ProfilePhoto::DISK)->response($user->photo_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Token is valid.', 'data' => ['user' => $this->userData($request->user())]]);
    }

    public function logout(Request $request, AppJwtService $jwt): JsonResponse
    {
        $user = $request->user();
        $jwt->revoke($user);
        $this->log($user, 'logged_out', $request);

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    private function authResponse(AppUser $user, array $token, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Authentication successful.', 'data' => [
            'user' => $this->userData($user),
            ...$token,
        ]], $status);
    }

    private function userData(AppUser $user): array
    {
        return $user->only([
            'id', 'name', 'email', 'phone', 'status', 'account_type', 'subscription_id',
            'subscription_package_name', 'subscription_package_id', 'subscription_package_type',
            'package_start_date', 'package_end_date', 'package_status', 'package_auto_renew',
            'platform', 'device_model', 'app_version', 'last_login_at',
        ]) + [
            'profile_photo_url' => $user->photo_path ? route('api.profile.photo') : null,
            'subscription' => $this->subscriptionData($user),
        ];
    }

    private function subscriptionData(AppUser $user): array
    {
        $today = Carbon::today();
        $state = $user->subscriptionState();
        $remaining = $state === 'active' && $user->package_end_date
            ? $today->diffInDays($user->package_end_date) + 1
            : 0;

        return [
            'account_type' => strtoupper((string) $user->account_type),
            'status' => $state,
            'subscription_id' => $user->subscription_id,
            'package_name' => $user->subscription_package_name,
            'package_id' => $user->subscription_package_id,
            'package_type' => $user->subscription_package_type,
            'start_date' => $user->package_start_date?->toDateString(),
            'end_date' => $user->package_end_date?->toDateString(),
            'days_remaining' => $remaining,
            'trial_used' => $user->trialAlreadyUsed(),
            'trial_start_date' => $user->trial_started_at?->toDateString(),
            'trial_end_date' => $user->trial_ends_at?->toDateString(),
            'auto_renew' => (bool) $user->package_auto_renew,
        ];
    }

    private function device(Request $request, array $data = []): array
    {
        return [...$data, 'ip' => $request->ip()];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return preg_replace('/[\s-]+/', '', trim($phone));
    }

    private function log(AppUser $user, string $event, Request $request, array $data = []): void
    {
        AppUserLog::create([
            'app_user_id' => $user->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'platform' => $data['platform'] ?? $user->platform,
            'device_model' => $data['device_model'] ?? $user->device_model,
            'app_version' => $data['app_version'] ?? $user->app_version,
            'fcm_token' => $data['fcm_token'] ?? $user->fcm_token,
            'metadata' => ['device_id' => $data['device_id'] ?? $user->api_device_id],
        ]);
    }
}
