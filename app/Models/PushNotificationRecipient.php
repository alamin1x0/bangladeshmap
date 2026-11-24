<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationRecipient extends Model
{
    protected $fillable = ['push_notification_id', 'app_user_id', 'token_hint', 'status', 'error', 'message_id'];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
