<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const CATEGORY_ADMIN = 'admin';

    public const CATEGORY_ALUMNI = 'alumni';

    public const UPDATED_AT = null;

    protected $fillable = [
        'category', 'user_id', 'actor_name', 'actor_role', 'impersonator_id', 'alumni_id',
        'action', 'description', 'succeeded', 'properties', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'succeeded' => 'boolean',
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }
}
