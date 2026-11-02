<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    protected $guarded = [];

    public function kawasan()
    {
        return $this->belongsTo(Kawasan::class);
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
}