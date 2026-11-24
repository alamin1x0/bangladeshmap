<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationSetController;
use App\Http\Controllers\AppUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingOrderController;
use App\Http\Controllers\LandingContentController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\WebSettingController;
use Illuminate\Support\Facades\Route;

// Icons, logo, manifest and robots: public and cached.
Route::get('/brand/{file}', [BrandingController::class, 'file'])->where('file', \App\Support\Branding::FILE_PATTERN)->name('brand.file');
Route::get('/favicon.ico', [BrandingController::class, 'favicon'])->name('favicon');
Route::get('/apple-touch-icon.png', [BrandingController::class, 'appleTouch']);
Route::get('/site.webmanifest', [BrandingController::class, 'manifest'])->name('manifest');
Route::get('/robots.txt', [BrandingController::class, 'robots']);
// Public landing page (book sales) + order form.
Route::get('/', [LandingController::class, 'show'])->name('home');
Route::post('/order', [LandingController::class, 'order'])->middleware('throttle:5,10')->name('landing.order');
// Notification images must be downloadable by phones without login: random 40-char names, strict pattern, JPEG only.
Route::get('/n-img/{file}', [\App\Http\Controllers\NotificationController::class, 'image'])->where('file', \App\Support\NotificationImage::FILENAME_PATTERN)->middleware('throttle:120,1')->name('notification-images.show');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/locations/divisions', [LocationController::class, 'divisions'])->name('locations.divisions');
    Route::get('/locations/districts', [LocationController::class, 'districts'])->name('locations.districts');
    Route::get('/locations/upazilas', [LocationController::class, 'upazilas'])->name('locations.upazilas');
    Route::get('/locations/{type}/create', [LocationController::class, 'create'])->whereIn('type', ['districts', 'upazilas'])->name('locations.create');
    Route::post('/locations/{type}', [LocationController::class, 'store'])->whereIn('type', ['districts', 'upazilas'])->name('locations.store');
    Route::delete('/locations/{type}/{id}', [LocationController::class, 'destroy'])->whereIn('type', ['districts', 'upazilas'])->whereNumber('id')->name('locations.destroy');
    Route::get('/locations/{type}/{id}/edit', [LocationController::class, 'edit'])->whereIn('type', ['divisions', 'districts', 'upazilas'])->name('locations.edit');
    Route::put('/locations/{type}/{id}', [LocationController::class, 'update'])->whereIn('type', ['divisions', 'districts', 'upazilas'])->name('locations.update');
    Route::delete('/locations/{type}/{id}/image', [LocationController::class, 'removeImage'])->whereIn('type', ['divisions', 'districts', 'upazilas'])->name('locations.image.remove');
    Route::get('/locations/{type}/{id}/image', [LocationController::class, 'image'])->whereIn('type', ['divisions', 'districts', 'upazilas'])->name('locations.image');
    Route::resource('location-sets', LocationSetController::class)->except(['show']);
    Route::resource('app-users', AppUserController::class)->except(['show']);
    Route::get('/app-users/{appUser}/logs', [AppUserController::class, 'logs'])->name('app-users.logs');
    Route::post('/app-users/{appUser}/start-trial', [AppUserController::class, 'startTrial'])->name('app-users.start-trial');
    Route::get('/app-users/{appUser}/photo', [AppUserController::class, 'photo'])->name('app-users.photo');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/subscription-payments', [SubscriptionPaymentController::class, 'index'])->name('subscription-payments.index');
    Route::post('/subscription-payments/{payment}/approve', [SubscriptionPaymentController::class, 'approve'])->name('subscription-payments.approve');
    Route::post('/subscription-payments/{payment}/reject', [SubscriptionPaymentController::class, 'reject'])->name('subscription-payments.reject');
    Route::get('/deletion-logs', [\App\Http\Controllers\DeletionLogController::class, 'index'])->name('deletion-logs.index');
    Route::get('/app-settings', [AppSettingController::class, 'edit'])->name('app-settings.edit');
    Route::put('/app-settings', [AppSettingController::class, 'update'])->name('app-settings.update');
    Route::get('/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('/notifications', [NotificationController::class, 'store'])->middleware('throttle:10,1')->name('notifications.store');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->whereNumber('notification')->name('notifications.show');
    Route::get('/location-sets/{locationSet}/image', [LocationSetController::class, 'image'])->name('location-sets.image');
    Route::view('/users', 'simple-page', ['title' => 'Users', 'description' => 'Manage administrator accounts and access status.'])->name('users');
    Route::view('/access-logs', 'simple-page', ['title' => 'Access Logs', 'description' => 'Review browser, IP address and device activity.'])->name('access-logs');
    Route::view('/settings', 'simple-page', ['title' => 'Settings', 'description' => 'Configure your GeoNexa admin workspace.'])->name('settings');
    Route::get('/web-settings', [WebSettingController::class, 'edit'])->name('web-settings.edit');
    Route::put('/web-settings', [WebSettingController::class, 'update'])->middleware('throttle:30,1')->name('web-settings.update');
    Route::get('/landing-page', [LandingContentController::class, 'edit'])->name('landing-content.edit');
    Route::put('/landing-page', [LandingContentController::class, 'update'])->middleware('throttle:30,1')->name('landing-content.update');
    Route::get('/landing-orders', [LandingOrderController::class, 'index'])->name('landing-orders.index');
    Route::put('/landing-orders/settings', [LandingOrderController::class, 'settings'])->name('landing-orders.settings');
    Route::patch('/landing-orders/{order}', [LandingOrderController::class, 'update'])->whereNumber('order')->name('landing-orders.update');
    Route::delete('/landing-orders/{order}', [LandingOrderController::class, 'destroy'])->whereNumber('order')->name('landing-orders.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
