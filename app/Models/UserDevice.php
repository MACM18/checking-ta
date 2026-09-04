<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_uuid',
        'device_name',
        'device_signature',
        'token_hash',
        'ip_address',
        'user_agent',
        'last_active_at',
        'expires_at',
        'is_revoked',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope only active, non-revoked, and unexpired devices.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_revoked', false)
            ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->is_revoked && ! $this->isExpired();
    }

    public function revoke(): void
    {
        $this->update(['is_revoked' => true]);
    }
}
