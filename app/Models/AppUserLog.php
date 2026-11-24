<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserLog extends Model
{
    protected $fillable = [
        'app_user_id', 'event', 'ip_address', 'user_agent', 'browser',
        'platform', 'device_model', 'app_version', 'fcm_token', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
