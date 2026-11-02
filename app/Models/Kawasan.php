<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kawasan extends Model
{
    protected $guarded = [];

    public function desas()
    {
        return $this->hasMany(Desa::class);
    }

    public function kelompoks()
    {
        return $this->hasMany(Kelompok::class);
    }

    public function nte()
    {
        return $this->hasOne(Nte::class);
    }
}