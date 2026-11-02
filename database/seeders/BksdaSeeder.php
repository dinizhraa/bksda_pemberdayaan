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
        // 1. Masukkan Data 18 Kawasan Konservasi & NTE
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
            [
                'nama_kawasan' => 'CA Pangi Binangga',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 8,
                'jumlah_desa_difasilitasi' => 6,
                'nte_langsung' => 20000000,
                'nte_tidak_langsung' => 30000000,
                'total_nte' => 50000000,
            ],
            [
                'nama_kawasan' => 'CA Saluki',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 7,
                'jumlah_desa_difasilitasi' => 5,
                'nte_langsung' => 15000000,
                'nte_tidak_langsung' => 25000000,
                'total_nte' => 40000000,
            ],
            [
                'nama_kawasan' => 'TWA Wera',
                'fungsi_kawasan' => 'TWA',
                'jumlah_desa_sekitar' => 6,
                'jumlah_desa_difasilitasi' => 4,
                'nte_langsung' => 18000000,
                'nte_tidak_langsung' => 22000000,
                'total_nte' => 40000000,
            ],
            [
                'nama_kawasan' => 'TWA Danau Poso',
                'fungsi_kawasan' => 'TWA',
                'jumlah_desa_sekitar' => 9,
                'jumlah_desa_difasilitasi' => 7,
                'nte_langsung' => 25000000,
                'nte_tidak_langsung' => 35000000,
                'total_nte' => 60000000,
            ],
            [
                'nama_kawasan' => 'CA Tanjung Toki',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 4,
                'jumlah_desa_difasilitasi' => 2,
                'nte_langsung' => 10000000,
                'nte_tidak_langsung' => 15000000,
                'total_nte' => 25000000,
            ],
            [
                'nama_kawasan' => 'CA Marisa',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 5,
                'jumlah_desa_difasilitasi' => 3,
                'nte_langsung' => 12000000,
                'nte_tidak_langsung' => 18000000,
                'total_nte' => 30000000,
            ],
            [
                'nama_kawasan' => 'TWA Morowali',
                'fungsi_kawasan' => 'TWA',
                'jumlah_desa_sekitar' => 6,
                'jumlah_desa_difasilitasi' => 4,
                'nte_langsung' => 14000000,
                'nte_tidak_langsung' => 20000000,
                'total_nte' => 34000000,
            ],
            [
                'nama_kawasan' => 'SM Banggai',
                'fungsi_kawasan' => 'SM',
                'jumlah_desa_sekitar' => 8,
                'jumlah_desa_difasilitasi' => 5,
                'nte_langsung' => 16000000,
                'nte_tidak_langsung' => 24000000,
                'total_nte' => 40000000,
            ],
            [
                'nama_kawasan' => 'CA Pulau Tokong Bolotu',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 3,
                'jumlah_desa_difasilitasi' => 2,
                'nte_langsung' => 9000000,
                'nte_tidak_langsung' => 11000000,
                'total_nte' => 20000000,
            ],
            [
                'nama_kawasan' => 'TWA Donggala',
                'fungsi_kawasan' => 'TWA',
                'jumlah_desa_sekitar' => 7,
                'jumlah_desa_difasilitasi' => 5,
                'nte_langsung' => 15000000,
                'nte_tidak_langsung' => 25000000,
                'total_nte' => 40000000,
            ],
            [
                'nama_kawasan' => 'CA Tokorondo',
                'fungsi_kawasan' => 'CA',
                'jumlah_desa_sekitar' => 10,
                'jumlah_desa_difasilitasi' => 6,
                'nte_langsung' => 22000000,
                'nte_tidak_langsung' => 28000000,
                'total_nte' => 50000000,
            ],
            [
                'nama_kawasan' => 'TWA Cabo',
                'fungsi_kawasan' => 'TWA',
                'jumlah_desa_sekitar' => 5,
                'jumlah_desa_difasilitasi' => 3,
                'nte_langsung' => 11000000,
                'nte_tidak_langsung' => 19000000,
                'total_nte' => 30000000,
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
        if ($bakiriang) {
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
        }

        $morowali = Kawasan::where('nama_kawasan', 'CA Morowali')->first();
        if ($morowali) {
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
}