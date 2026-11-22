<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    /**
     * @var array<string, string>
     */
    public const TABLE_COLUMNS = [
        'No' => 'no',
        'Tahun Pembinaan' => 'tahun_pembinaan',
        'Tahun Pembentukan' => 'tahun_pembentukan',
        'Tahun Monitoring' => 'tahun_monitoring',
        'Status Kelompok' => 'status_kelompok',
        'Kawasan' => 'kawasan.nama_kawasan',
        'Desa' => 'desa.nama_desa',
        'Kecamatan' => 'kecamatan',
        'Kabupaten' => 'kabupaten',
        'Nama Kelompok' => 'nama_kelompok',
        'Nama Ketua' => 'nama_ketua',
        'No HP' => 'no_hp',
        'Latitude' => 'latitude',
        'Longitude' => 'longitude',
        'Jenis Usaha' => 'jenis_usaha',
        'SK Kelompok' => 'sk_kelompok',
        'Jumlah Anggota Laki-laki' => 'jumlah_anggota_laki_laki',
        'Jumlah Anggota Perempuan' => 'jumlah_anggota_perempuan',
        'Total Anggota' => 'jumlah_anggota',
        'Jumlah Bantuan (Rp)' => 'jumlah_bantuan',
        'Catatan Kebutuhan / Hasil Monev' => 'catatan_kebutuhan_hasil_monev',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tahun_pembinaan' => 'integer',
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
