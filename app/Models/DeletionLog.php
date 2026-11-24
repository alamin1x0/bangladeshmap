<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Append-only audit trail of everything an admin deletes. */
class DeletionLog extends Model
{
    public const TYPES = [
        'district' => 'District',
        'upazila' => 'Upazila / Thana',
        'location_set' => 'Location set',
        'app_user' => 'App user',
        'landing_order' => 'Book order',
    ];

    protected $fillable = [
        'admin_user_id', 'admin_name', 'entity_type', 'entity_id', 'entity_name', 'entity_code',
        'parent_name', 'children_count', 'snapshot', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->entity_type] ?? $this->entity_type;
    }

    /**
     * Record a deletion. Call it right BEFORE ->delete() (inside the same transaction when there is one)
     * so the snapshot still holds the full row. Hidden attributes (passwords, tokens) are never stored.
     */
    public static function record(string $type, Model $model, ?string $name = null, ?string $parent = null, array $children = []): void
    {
        try {
            $admin = Auth::user();
            $request = request();
            self::create([
                'admin_user_id' => $admin?->getKey(),
                'admin_name' => $admin->username ?? null,
                'entity_type' => $type,
                'entity_id' => $model->getKey(),
                'entity_name' => $name ?? ($model->name ?? null),
                'entity_code' => $model->code ?? null,
                'parent_name' => $parent,
                'children_count' => count($children),
                'snapshot' => ['record' => $model->toArray(), 'children' => $children],
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            // Never block a delete because logging failed, but leave a trace.
            Log::error('Deletion log failed: '.$e->getMessage(), ['type' => $type, 'id' => $model->getKey()]);
        }
    }
}
