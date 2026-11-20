<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NteSummary extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nte_langsung' => 'decimal:2',
            'nte_tidak_langsung' => 'decimal:2',
            'total_nte' => 'decimal:2',
            'nte_langsung_belum_teralokasi' => 'decimal:2',
        ];
    }
}
