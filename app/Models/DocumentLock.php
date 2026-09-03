<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLock extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'locked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
