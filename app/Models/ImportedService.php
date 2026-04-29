<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedService extends Model
{
    protected $fillable = [
        'source_path',
        'service_uri',
        'record_id',
        'status',
        'service_type',
        'title',
        'provider_name',
        'url',
        'error_message',
        'imported_by',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

