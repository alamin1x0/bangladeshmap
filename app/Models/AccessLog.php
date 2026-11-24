<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'ip_address',
        'method',
        'path',
        'referer',
        'user_agent',
        'browser',
        'platform',
        'device',
        'country',
        'city',
        'latitude',
        'longitude',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}
