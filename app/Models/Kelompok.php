<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tahun_pembentukan' => 'integer',
            'tahun_monitoring' => 'integer',
        ];
    }

    public function kawasan()
    {
        return $this->belongsTo(Kawasan::class);
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
}
