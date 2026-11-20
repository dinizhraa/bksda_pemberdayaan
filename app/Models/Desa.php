<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Desa extends Model
{
    protected $guarded = [];

    // Relasi lama: satu desa memiliki satu kawasan utama
    public function kawasan(): BelongsTo
    {
        return $this->belongsTo(Kawasan::class);
    }

    // Relasi baru: satu desa dapat terhubung ke beberapa kawasan
    public function kawasans(): BelongsToMany
    {
        return $this->belongsToMany(Kawasan::class, 'desa_kawasan')
            ->withTimestamps();
    }
}
