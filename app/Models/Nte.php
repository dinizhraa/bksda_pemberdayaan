<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nte extends Model
{
    protected $guarded = [];

    public function kawasan()
    {
        return $this->belongsTo(Kawasan::class);
    }
}