<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Website-wide settings (branding, status, contact, toggles), cached; only changed values are stored. */
class WebSettings
{
    public const CACHE_KEY = 'web:settings:v1';

    public const STATUSES = ['live', 'maintenance', 'down'];

    /** key => default (type follows the default: bool, string). */
    public const DEFAULTS = [
        // Branding
        'site_name' => 'GeoNexa',
        'tagline' => 'Bangladesh: Every Upazila Unveiled',
        'theme_color' => '#1f4d3a',
        'logo' => '',
        'favicon' => '',
        // Status
        'site_status' => 'live',
        'maintenance_title' => 'আমরা ওয়েবসাইটটি আপডেট করছি',
        'maintenance_message' => 'কিছুক্ষণের মধ্যেই আবার চালু হবে। অর্ডার বা প্রশ্নের জন্য নিচের নম্বরে যোগাযোগ করুন।',
        'maintenance_until' => '',
        'down_message' => 'ওয়েবসাইটটি এই মুহূর্তে বন্ধ আছে। পরে আবার চেষ্টা করুন।',
        // Contact
        'support_phone' => '',
        'support_email' => '',
        'whatsapp' => '',
        'facebook_url' => '',
        'youtube_url' => '',
        'address' => '',
        'office_hours' => '',
        // Announcement
        'announcement_on' => false,
        'announcement_text' => '',
        'announcement_link' => '',
        // Page sections
        'show_preview' => true,
        'show_navigate' => true,
        'show_audience' => true,
        'show_pricing_table' => true,
        'show_faq' => true,
        'show_cta' => true,
        // Mobile app
        'app_on' => false,
        'app_name' => 'GeoNexa',
        'app_title' => 'মোবাইল অ্যাপে সব মানচিত্র, সবসময় হাতের কাছে',
        'app_text' => 'যেকোনো উপজেলা খুঁজুন, জুম করে দেখুন — ইন্টারনেট ছাড়াও। এখনই ডাউনলোড করুন।',
        'app_rating' => '',
        'app_downloads' => '',
        'play_url' => '',
        'ios_url' => '',
        'play_on' => true,
        'ios_on' => true,
        'app_in_nav' => true,
        'app_in_footer' => true,
        // Advanced
        'whatsapp_button' => false,
        'mobile_buy_bar' => true,
        'show_admin_link' => true,
        'allow_indexing' => true,
        'track_visitors' => true,
        'show_contact_footer' => true,
    ];

    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return DB::table('web_settings')->pluck('value', 'key')->all();
            } catch (\Throwable) {
                return [];
            }
        });
        $out = self::DEFAULTS;
        foreach (array_intersect_key($stored, self::DEFAULTS) as $key => $value) {
            $out[$key] = is_bool(self::DEFAULTS[$key]) ? $value === '1' : (string) $value;
        }

        return $out;
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }

    public static function save(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            $value = is_bool(self::DEFAULTS[$key]) ? ($value ? '1' : '0') : trim((string) $value);
            $default = is_bool(self::DEFAULTS[$key]) ? (self::DEFAULTS[$key] ? '1' : '0') : self::DEFAULTS[$key];
            if ($value === $default) {
                DB::table('web_settings')->where('key', $key)->delete();
            } else {
                DB::table('web_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
            }
        }
        Cache::forget(self::CACHE_KEY);
    }

    /** Phone shown on the website: Web settings → Book orders phone → App support phone. */
    public static function phone(): ?string
    {
        $phone = self::get('support_phone');
        if ($phone !== '') {
            return $phone;
        }
        $app = AppSetting::current();

        return $app->landing_phone ?: ($app->support_phone ?: null);
    }

    /** Store links that are switched on and filled in. */
    public static function appLinks(): array
    {
        $s = self::all();
        $links = [];
        if ($s['play_on'] && $s['play_url'] !== '') {
            $links['play'] = $s['play_url'];
        }
        if ($s['ios_on'] && $s['ios_url'] !== '') {
            $links['ios'] = $s['ios_url'];
        }

        return $s['app_on'] ? $links : [];
    }

    public static function isPublicBlocked(): bool
    {
        return self::get('site_status') !== 'live' && ! auth()->check();
    }

    /** Icon URLs: uploaded set if present, otherwise the built-in GeoNexa icon. */
    public static function icons(): array
    {
        $fav = self::get('favicon');
        $u = fn (string $file) => route('brand.file', ['file' => $file]);

        return [
            'svg' => $fav ? null : $u('favicon.svg'),
            'ico' => $fav ? $u($fav.'-favicon.ico') : $u('favicon.ico'),
            '32' => $u($fav ? $fav.'-32.png' : 'icon-32.png'),
            '180' => $u($fav ? $fav.'-180.png' : 'icon-180.png'),
            '192' => $u($fav ? $fav.'-192.png' : 'icon-192.png'),
            '512' => $u($fav ? $fav.'-512.png' : 'icon-512.png'),
        ];
    }

    public static function logoUrl(): ?string
    {
        $logo = self::get('logo');

        return $logo ? route('brand.file', ['file' => $logo]) : null;
    }
}
