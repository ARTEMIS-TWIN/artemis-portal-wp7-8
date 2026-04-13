<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedHeritageEntity extends Model
{
    protected $fillable = [
        'graph_uri',
        'entity_uri',
        'record_id',
        'status',
        'entity_type',
        'label',
        'place_label',
        'country_label',
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
