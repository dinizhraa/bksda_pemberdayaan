<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kawasan;
use App\Models\Nte;
use App\Models\Kelompok;
use App\Models\Desa;

class BksdaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Masukkan Data Kawasan & NTE
        $dataKawasan = [
            [
                'nama_kawasan' => 'SM Bakiriang',
                'fungsi_kawasan' => 'SM',
                'jumlah_desa_sekitar' => 12,
                'jumlah_desa_difasilitasi' => 11,
                'nte_langsung' => 36180000,
                'nte_tidak_langsung' => 85440000,
                'total_nte' => 121620000,
            ],
            [
                'nama_kawasan' => 'CA Morowali',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 17,
                'jumlah_desa_difasilitasi' => 10,
                'nte_langsung' => 64830000,
                'nte_tidak_langsung' => 119350000,
                'total_nte' => 184180000,
            ],
            [
                'nama_kawasan' => 'CA Pamona',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 11,
                'jumlah_desa_difasilitasi' => 10,
                'nte_langsung' => 38250000,
                'nte_tidak_langsung' => 546675000,
                'total_nte' => 584925000,
            ],
            [
                'nama_kawasan' => 'CA Gunung Dako',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 10,
                'jumlah_desa_difasilitasi' => 9,
                'nte_langsung' => 24600000,
                'nte_tidak_langsung' => 9945000,
                'total_nte' => 34545000,
            ],
            [
                'nama_kawasan' => 'CA Gunung Sojol',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 34,
                'jumlah_desa_difasilitasi' => 3,
                'nte_langsung' => 39900000,
                'nte_tidak_langsung' => 7920000,
                'total_nte' => 47820000,
            ],
            [
                'nama_kawasan' => 'TB Londusa Tomata',
                'fungsi_kawasan' => 'TB',
                'jumlah_desa_sekitar' => 5,
                'jumlah_desa_difasilitasi' => 3,
                'nte_langsung' => 40950000,
                'nte_tidak_langsung' => 63500000,
                'total_nte' => 104450000,
            ],
        ];

        foreach ($dataKawasan as $item) {
            $kawasan = Kawasan::create([
                'nama_kawasan' => $item['nama_kawasan'],
                'fungsi_kawasan' => $item['fungsi_kawasan'],
                'jumlah_desa_sekitar' => $item['jumlah_desa_sekitar'],
                'jumlah_desa_difasilitasi' => $item['jumlah_desa_difasilitasi'],
            ]);

            Nte::create([
                'kawasan_id' => $kawasan->id,
                'tahun' => 2025,
                'nte_langsung' => $item['nte_langsung'],
                'nte_tidak_langsung' => $item['nte_tidak_langsung'],
                'total_nte' => $item['total_nte'],
            ]);
        }

        // 2. Masukkan Contoh Data Kelompok Binaan
        $bakiriang = Kawasan::where('nama_kawasan', 'SM Bakiriang')->first();
        Kelompok::create([
            'kawasan_id' => $bakiriang->id,
            'tahun_pembinaan' => 2024,
            'nama_kelompok' => 'Kelompok Tani Ternak Makmur',
            'nama_ketua' => 'Bapak Sulaeman',
            'no_hp' => '082112345678',
            'jenis_usaha' => 'Ternak Kambing & Jagung',
            'produk_unggulan' => 'Ternak Kambing',
            'sk_kelompok' => 'SK.01/BKSDA/2024',
            'jumlah_anggota' => 15,
            'jumlah_bantuan' => 47625000,
        ]);

        $morowali = Kawasan::where('nama_kawasan', 'CA Morowali')->first();
        Kelompok::create([
            'kawasan_id' => $morowali->id,
            'tahun_pembinaan' => 2025,
            'nama_kelompok' => 'Masyarakat Peduli Hutan',
            'nama_ketua' => 'Ibu Halimah',
            'no_hp' => '081298765432',
            'jenis_usaha' => 'Pengolahan Lebah Madu & Kerajinan',
            'produk_unggulan' => 'Lebah Madu',
            'sk_kelompok' => 'SK.05/BKSDA/2025',
            'jumlah_anggota' => 20,
            'jumlah_bantuan' => 23400000,
        ]);
    }
}