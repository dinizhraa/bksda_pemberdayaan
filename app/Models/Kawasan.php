<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kawasan extends Model
{
    protected $guarded = [];

    // Relasi lama: desa melalui kawasan_id
    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class);
    }

    // Relasi baru: kawasan dapat memiliki banyak desa
    public function semuaDesa(): BelongsToMany
    {
        return $this->belongsToMany(Desa::class, 'desa_kawasan')
            ->withTimestamps();
    }

    public function kelompoks(): HasMany
    {
        return $this->hasMany(Kelompok::class);
    }

    public function nte(): HasOne
    {
        return $this->hasOne(Nte::class);
    }
}
