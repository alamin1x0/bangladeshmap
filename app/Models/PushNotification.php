<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PushNotification extends Model
{
    protected $fillable = [
        'title', 'body', 'image_url', 'image_path', 'link', 'audience', 'audience_detail',
        'target_count', 'success_count', 'failure_count', 'status', 'admin_user_id',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(PushNotificationRecipient::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /** Uploaded image (served from this server) or the pasted https link. */
    public function imageSrc(): ?string
    {
        return $this->image_path ? \App\Support\NotificationImage::url($this->image_path) : $this->image_url;
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'all' => 'All active users',
            'platform' => ($this->audience_detail ?: 'Platform').' users',
            'users' => $this->target_count === 1 ? 'Selected user' : 'Selected users',
            'token' => 'Test device token',
            default => ucfirst((string) $this->audience),
        };
    }
}
