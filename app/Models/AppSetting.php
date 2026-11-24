<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AppSetting extends Model
{
    protected $fillable = [
        'screen_lock_enabled', 'screenshot_enabled', 'screen_recording_enabled',
        'short_video_enabled', 'server_down', 'force_update', 'minimum_app_version',
        'maintenance_message', 'support_phone', 'support_email', 'bkash_merchant_number', 'force_logout_at',
        'landing_price', 'landing_phone', 'landing_orders_open',
    ];

    protected function casts(): array
    {
        return [
            'screen_lock_enabled' => 'boolean',
            'screenshot_enabled' => 'boolean',
            'screen_recording_enabled' => 'boolean',
            'short_video_enabled' => 'boolean',
            'server_down' => 'boolean',
            'force_update' => 'boolean',
            'force_logout_at' => 'datetime',
            'landing_orders_open' => 'boolean',
            'landing_price' => 'integer',
        ];
    }

    public static function current(): self
    {
        $settings = static::query()->firstOrCreate(['id' => 1], [
            'screen_lock_enabled' => false,
            'screenshot_enabled' => true,
            'screen_recording_enabled' => true,
            'short_video_enabled' => true,
            'server_down' => false,
            'force_update' => false,
        ]);
        $defaults = [
            'screen_lock_enabled' => false,
            'screenshot_enabled' => true,
            'screen_recording_enabled' => true,
            'short_video_enabled' => true,
            'server_down' => false,
            'force_update' => false,
        ];
        $changed = false;
        foreach ($defaults as $field => $value) {
            if ($settings->{$field} === null) {
                $settings->{$field} = $value;
                $changed = true;
            }
        }
        if ($changed) {
            $settings->save();
        }

        return $settings;
    }

    public function apiData(): array
    {
        return [
            'screen_lock_enabled' => $this->screen_lock_enabled,
            'screenshot_enabled' => $this->screenshot_enabled,
            'screen_recording_enabled' => $this->screen_recording_enabled,
            'short_video_enabled' => $this->short_video_enabled,
            'server_down' => $this->server_down,
            'force_update' => $this->force_update,
            'minimum_app_version' => $this->minimum_app_version,
            'maintenance_message' => $this->maintenance_message,
            'support_phone' => $this->support_phone,
            'support_email' => $this->support_email,
            'bkash_merchant_number' => $this->bkash_merchant_number,
            'force_logout_at' => $this->force_logout_at?->toIso8601String(),
        ];
    }
}
