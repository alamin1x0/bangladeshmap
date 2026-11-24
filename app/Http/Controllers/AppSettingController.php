<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\AppJwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppSettingController extends Controller
{
    public function edit(): View
    {
        return view('app-settings.edit', ['settings' => AppSetting::current()]);
    }

    public function update(Request $request, AppJwtService $jwt): RedirectResponse
    {
        $data = $request->validate([
            'minimum_app_version' => ['nullable', 'string', 'max:30'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email:rfc', 'max:190'],
            'bkash_merchant_number' => ['nullable', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
        ]);
        $settings = AppSetting::current();
        $settings->fill($data);
        foreach (['screen_lock_enabled', 'screenshot_enabled', 'screen_recording_enabled', 'short_video_enabled', 'server_down', 'force_update'] as $field) {
            $settings->{$field} = $request->boolean($field);
        }
        if ($request->boolean('force_logout')) {
            $jwt->revokeAll();
            $settings->force_logout_at = now();
        }
        $settings->save();
        \Illuminate\Support\Facades\Cache::forget(\App\Http\Controllers\LandingController::CACHE_KEY);

        return back()->with('success', $request->boolean('force_logout')
            ? 'Settings saved and all app users were logged out.'
            : 'App settings saved successfully.');
    }
}
