<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use App\Support\WebSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WebSettingController extends Controller
{
    public function edit(): View
    {
        return view('web-settings.edit', [
            's' => WebSettings::all(),
            'icons' => WebSettings::icons(),
            'logoUrl' => WebSettings::logoUrl(),
            'fallbackPhone' => WebSettings::phone(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $url = ['nullable', 'string', 'max:300', 'url:https,http'];
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'theme_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => Branding::uploadRules(),
            'favicon' => Branding::uploadRules(),
            'site_status' => ['required', Rule::in(WebSettings::STATUSES)],
            'maintenance_title' => ['nullable', 'string', 'max:150'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
            'maintenance_until' => ['nullable', 'date'],
            'down_message' => ['nullable', 'string', 'max:1000'],
            'support_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'support_email' => ['nullable', 'email:rfc', 'max:190'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            'facebook_url' => $url,
            'youtube_url' => $url,
            'address' => ['nullable', 'string', 'max:300'],
            'office_hours' => ['nullable', 'string', 'max:120'],
            'announcement_text' => ['nullable', 'string', 'max:200', 'required_if_accepted:announcement_on'],
            'announcement_link' => $url,
            'app_name' => ['nullable', 'string', 'max:60'],
            'app_title' => ['nullable', 'string', 'max:150'],
            'app_text' => ['nullable', 'string', 'max:400'],
            'app_rating' => ['nullable', 'string', 'max:20'],
            'app_downloads' => ['nullable', 'string', 'max:40'],
            'play_url' => ['nullable', 'string', 'max:300', 'url:https', 'regex:#^https://play\.google\.com/#i'],
            'ios_url' => ['nullable', 'string', 'max:300', 'url:https', 'regex:#^https://(apps|itunes)\.apple\.com/#i'],
        ], [
            'play_url.regex' => 'Google Play link must start with https://play.google.com/',
            'ios_url.regex' => 'App Store link must start with https://apps.apple.com/',
            'play_url.url' => 'Google Play link must be a full https:// link.',
            'ios_url.url' => 'App Store link must be a full https:// link.',
            'announcement_text.required_if_accepted' => 'Write the announcement text, or turn the announcement bar off.',
            'whatsapp.regex' => 'WhatsApp number must be digits only, e.g. 8801712345678.',
        ], [
            'theme_color' => 'brand color', 'support_phone' => 'support phone', 'support_email' => 'support email',
        ]);

        if ($request->boolean('app_on') && ! $request->filled('play_url') && ! $request->filled('ios_url')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['play_url' => 'Add at least one store link (Google Play or App Store) to show the app section.']);
        }
        $current = WebSettings::all();
        $values = collect($data)->except(['logo', 'favicon'])->map(fn ($v) => $v ?? '')->all();
        $values['theme_color'] = strtolower($values['theme_color']);
        $values['maintenance_until'] = $request->filled('maintenance_until') ? (string) $request->input('maintenance_until') : '';
        foreach (array_keys(array_filter(WebSettings::DEFAULTS, 'is_bool')) as $flag) {
            $values[$flag] = $request->boolean($flag);
        }

        // Images: new upload replaces the old file; "remove" goes back to the built-in icon / text logo.
        foreach (['logo' => 'storeLogo', 'favicon' => 'storeFavicon'] as $field => $store) {
            if ($request->hasFile($field)) {
                $values[$field] = Branding::$store($request->file($field));
                Branding::delete($current[$field]);
            } elseif ($request->boolean('remove_'.$field)) {
                $values[$field] = '';
                Branding::delete($current[$field]);
            }
        }

        WebSettings::save($values);
        Cache::forget(LandingController::CACHE_KEY);

        $msg = match ($values['site_status']) {
            'maintenance' => 'Settings saved. The website now shows the maintenance page to visitors.',
            'down' => 'Settings saved. The website is now offline for visitors.',
            default => 'Website settings saved.',
        };

        return redirect()->route('web-settings.edit', ['tab' => $request->input('tab')])->with('success', $msg);
    }
}
