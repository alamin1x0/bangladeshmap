<?php

use App\Http\Controllers\Api\AppAuthController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Middleware\AppApiAuth;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('throttle:10,1')->group(function (): void {
    Route::post('/register', [AppAuthController::class, 'register']);
    Route::post('/check-email', [AppAuthController::class, 'checkEmail']);
    Route::post('/login', [AppAuthController::class, 'login']);
    Route::post('/refresh', [AppAuthController::class, 'refresh']);
});

Route::get('v1/app-settings', fn () => response()->json([
    'success' => true,
    'data' => ['settings' => AppSetting::current()->apiData()],
]));

Route::prefix('v1')->middleware(AppApiAuth::class)->group(function (): void {
    Route::get('/me', [AppAuthController::class, 'me']);
    Route::get('/auth/check', [AppAuthController::class, 'check']);
    Route::post('/auth/logout', [AppAuthController::class, 'logout']);
    Route::get('/subscription/status', [AppAuthController::class, 'subscriptionStatus']);
    Route::post('/subscription/start-trial', [AppAuthController::class, 'startTrial']);
    Route::post('/subscription/payment', [AppAuthController::class, 'submitPayment']);
    Route::get('/subscription/payments', [AppAuthController::class, 'paymentStatus']);
    Route::get('/profile', [AppAuthController::class, 'profile']);
    Route::match(['put', 'patch'], '/profile', [AppAuthController::class, 'updateProfile']);
    Route::post('/profile', [AppAuthController::class, 'updateProfile']);
    Route::get('/profile/photo', [AppAuthController::class, 'profilePhoto'])->name('api.profile.photo');
    Route::get('/divisions', [LocationApiController::class, 'divisions']);
    Route::get('/divisions/{id}/map', [LocationApiController::class, 'mapImage'])->whereNumber('id')->name('api.divisions.map');
    Route::get('/districts', [LocationApiController::class, 'districts']);
    Route::get('/districts/{id}/map', [LocationApiController::class, 'mapImage'])->whereNumber('id')->name('api.districts.map');
    Route::get('/upazilas', [LocationApiController::class, 'upazilas']);
    Route::get('/upazilas/{id}/map', [LocationApiController::class, 'mapImage'])->whereNumber('id')->name('api.upazilas.map');
});
