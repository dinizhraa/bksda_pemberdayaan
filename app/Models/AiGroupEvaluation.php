<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kelompok_id',
    'user_id',
    'priority',
    'summary',
    'rationale',
    'findings',
    'recommendations',
    'source_hash',
    'evaluated_at',
])]
class AiGroupEvaluation extends Model
{
    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'recommendations' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
