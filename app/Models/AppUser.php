<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUser extends Authenticatable
{
    protected $table = 'app_users';

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'status', 'account_type', 'subscription_id',
        'subscription_package_name', 'subscription_package_id', 'subscription_package_type',
        'package_start_date', 'package_end_date', 'package_status', 'package_auto_renew',
        'fcm_token', 'app_version', 'platform', 'device_model', 'last_ip_address', 'last_login_at',
        'api_token_hash', 'api_refresh_token_hash', 'api_token_issued_at', 'api_token_expires_at',
        'api_refresh_token_expires_at', 'api_device_id',
        'trial_started_at', 'trial_ends_at',
    ];

    protected $hidden = ['password', 'remember_token', 'fcm_token', 'api_token_hash', 'api_refresh_token_hash'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'package_start_date' => 'date',
            'package_end_date' => 'date',
            'package_auto_renew' => 'boolean',
            'api_token_issued_at' => 'datetime',
            'api_token_expires_at' => 'datetime',
            'api_refresh_token_expires_at' => 'datetime',
            'trial_started_at' => 'date',
            'trial_ends_at' => 'date',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AppUserLog::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(SubscriptionPaymentRequest::class);
    }

    public function hasActiveSubscription(): bool
    {
        $today = Carbon::today();

        return in_array($this->account_type, ['paid', 'trial'], true)
            && $this->package_status === 'active'
            && $this->package_start_date?->lte($today)
            && $this->package_end_date?->gte($today);
    }

    public function subscriptionState(): string
    {
        $today = Carbon::today();

        if (! $this->package_start_date || ! $this->package_end_date) {
            return 'none';
        }
        if ($this->package_status === 'cancelled') {
            return 'cancelled';
        }
        if ($this->package_status === 'pending' || $this->package_start_date->isFuture()) {
            return 'pending';
        }
        if ($this->package_status !== 'active' || $this->package_end_date->lt($today)) {
            return 'expired';
        }

        return 'active';
    }

    public function trialAlreadyUsed(): bool
    {
        return filled($this->trial_started_at);
    }

    /**
     * URL of the profile photo (admin-only route), or null. The ?v= part busts the browser
     * cache when the photo changes, because a new upload always gets a new random file name.
     * photo_path is intentionally not fillable: it is only set by the controller after processing.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path
            ? route('app-users.photo', ['appUser' => $this->id, 'v' => substr(md5($this->photo_path), 0, 10)])
            : null);
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => mb_strtoupper(mb_substr(trim((string) $this->name) ?: '?', 0, 1)));
    }
}
