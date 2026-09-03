<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'snapshot_data',
        'change_summary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'snapshot_data' => 'array',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
