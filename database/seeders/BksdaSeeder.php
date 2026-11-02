<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kawasan;
use App\Models\Nte;
use App\Models\Kelompok;

class BksdaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Masukkan Data 18 Kawasan Konservasi & NTE
        $dataKawasan = [
            ['nama_kawasan' => 'SM Bakiriang', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 12, 'jumlah_desa_difasilitasi' => 11, 'nte_langsung' => 36180000, 'nte_tidak_langsung' => 85440000, 'total_nte' => 121620000],
            ['nama_kawasan' => 'CA Morowali', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 17, 'jumlah_desa_difasilitasi' => 10, 'nte_langsung' => 64830000, 'nte_tidak_langsung' => 119350000, 'total_nte' => 184180000],
            ['nama_kawasan' => 'CA Pamona', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 11, 'jumlah_desa_difasilitasi' => 10, 'nte_langsung' => 38250000, 'nte_tidak_langsung' => 546675000, 'total_nte' => 584925000],
            ['nama_kawasan' => 'CA Gunung Dako', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 10, 'jumlah_desa_difasilitasi' => 9, 'nte_langsung' => 24600000, 'nte_tidak_langsung' => 9945000, 'total_nte' => 34545000],
            ['nama_kawasan' => 'CA Gunung Sojol', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 34, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 39900000, 'nte_tidak_langsung' => 7920000, 'total_nte' => 47820000],
            ['nama_kawasan' => 'TB Londusa Tomata', 'fungsi_kawasan' => 'TB', 'jumlah_desa_sekitar' => 5, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 40950000, 'nte_tidak_langsung' => 63500000, 'total_nte' => 104450000],
            ['nama_kawasan' => 'CA Pangi Binangga', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 8, 'jumlah_desa_difasilitasi' => 6, 'nte_langsung' => 20000000, 'nte_tidak_langsung' => 30000000, 'total_nte' => 50000000],
            ['nama_kawasan' => 'CA Saluki', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 7, 'jumlah_desa_difasilitasi' => 5, 'nte_langsung' => 15000000, 'nte_tidak_langsung' => 25000000, 'total_nte' => 40000000],
            ['nama_kawasan' => 'TWA Wera', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 6, 'jumlah_desa_difasilitasi' => 4, 'nte_langsung' => 18000000, 'nte_tidak_langsung' => 22000000, 'total_nte' => 40000000],
            ['nama_kawasan' => 'TWA Danau Poso', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 9, 'jumlah_desa_difasilitasi' => 7, 'nte_langsung' => 25000000, 'nte_tidak_langsung' => 35000000, 'total_nte' => 60000000],
            ['nama_kawasan' => 'CA Tanjung Toki', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 4, 'jumlah_desa_difasilitasi' => 2, 'nte_langsung' => 10000000, 'nte_tidak_langsung' => 15000000, 'total_nte' => 25000000],
            ['nama_kawasan' => 'CA Marisa', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 5, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 12000000, 'nte_tidak_langsung' => 18000000, 'total_nte' => 30000000],
            ['nama_kawasan' => 'TWA Morowali', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 6, 'jumlah_desa_difasilitasi' => 4, 'nte_langsung' => 14000000, 'nte_tidak_langsung' => 20000000, 'total_nte' => 34000000],
            ['nama_kawasan' => 'SM Banggai', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 8, 'jumlah_desa_difasilitasi' => 5, 'nte_langsung' => 16000000, 'nte_tidak_langsung' => 24000000, 'total_nte' => 40000000],
            ['nama_kawasan' => 'CA Pulau Tokong Bolotu', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 3, 'jumlah_desa_difasilitasi' => 2, 'nte_langsung' => 9000000, 'nte_tidak_langsung' => 11000000, 'total_nte' => 20000000],
            ['nama_kawasan' => 'TWA Donggala', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 7, 'jumlah_desa_difasilitasi' => 5, 'nte_langsung' => 15000000, 'nte_tidak_langsung' => 25000000, 'total_nte' => 40000000],
            ['nama_kawasan' => 'CA Tokorondo', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 10, 'jumlah_desa_difasilitasi' => 6, 'nte_langsung' => 22000000, 'nte_tidak_langsung' => 28000000, 'total_nte' => 50000000],
            ['nama_kawasan' => 'TWA Cabo', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 5, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 11000000, 'nte_tidak_langsung' => 19000000, 'total_nte' => 30000000],
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

        // 2. Masukkan Daftar Kelompok Binaan Lengkap
        $daftarKelompok = [
            [
                'kawasan' => 'SM Bakiriang',
                'tahun' => 2024,
                'nama' => 'Kelompok Tani Ternak Makmur',
                'ketua' => 'Bapak Sulaeman',
                'hp' => '082112345678',
                'usaha' => 'Ternak Kambing & Jagung',
                'produk' => 'Ternak Kambing',
                'sk' => 'SK.01/BKSDA/2024',
                'anggota' => 15,
                'bantuan' => 47625000,
            ],
            [
                'kawasan' => 'CA Morowali',
                'tahun' => 2025,
                'nama' => 'Masyarakat Peduli Hutan',
                'ketua' => 'Ibu Halimah',
                'hp' => '081298765432',
                'usaha' => 'Pengolahan Lebah Madu & Kerajinan',
                'produk' => 'Lebah Madu Hutan',
                'sk' => 'SK.05/BKSDA/2025',
                'anggota' => 20,
                'bantuan' => 23400000,
            ],
            [
                'kawasan' => 'CA Pamona',
                'tahun' => 2024,
                'nama' => 'KWT Agro Lestarikan',
                'ketua' => 'Ibu Maria',
                'hp' => '085233441122',
                'usaha' => 'Budidaya Kopi & Kokoa',
                'produk' => 'Kopi Organik',
                'sk' => 'SK.08/BKSDA/2024',
                'anggota' => 18,
                'bantuan' => 35000000,
            ],
            [
                'kawasan' => 'CA Gunung Dako',
                'tahun' => 2025,
                'nama' => 'Kelompok Konservasi Dako Mandiri',
                'ketua' => 'Bapak Hasan',
                'hp' => '081377889900',
                'usaha' => 'Pengolahan Rotan & Gula Aren',
                'produk' => 'Gula Aren Cetak',
                'sk' => 'SK.12/BKSDA/2025',
                'anggota' => 12,
                'bantuan' => 20000000,
            ],
            [
                'kawasan' => 'TWA Wera',
                'tahun' => 2025,
                'nama' => 'Pokdarwis Pesona Air Terjun',
                'ketua' => 'Bapak Ahmad',
                'hp' => '082299887766',
                'usaha' => 'Jasa Wisata Alam & Kuliner Lokal',
                'produk' => 'Paket Ekowisata',
                'sk' => 'SK.15/BKSDA/2025',
                'anggota' => 25,
                'bantuan' => 30000000,
            ],
            [
                'kawasan' => 'CA Pangi Binangga',
                'tahun' => 2024,
                'nama' => 'Kelompok Tani Hijau Lestari',
                'ketua' => 'Bapak Ruslan',
                'hp' => '085344556677',
                'usaha' => 'Budidaya Durian & Alpukat',
                'produk' => 'Bibit Buah Unggul',
                'sk' => 'SK.18/BKSDA/2024',
                'anggota' => 16,
                'bantuan' => 25000000,
            ]
        ];

        foreach ($daftarKelompok as $klp) {
            $kawasanObj = Kawasan::where('nama_kawasan', $klp['kawasan'])->first();
            if ($kawasanObj) {
                Kelompok::create([
                    'kawasan_id' => $kawasanObj->id,
                    'tahun_pembinaan' => $klp['tahun'],
                    'nama_kelompok' => $klp['nama'],
                    'nama_ketua' => $klp['ketua'],
                    'no_hp' => $klp['hp'],
                    'jenis_usaha' => $klp['usaha'],
                    'produk_unggulan' => $klp['produk'],
                    'sk_kelompok' => $klp['sk'],
                    'jumlah_anggota' => $klp['anggota'],
                    'jumlah_bantuan' => $klp['bantuan'],
                ]);
            }
        }
    }
}