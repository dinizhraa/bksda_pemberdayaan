<?php

namespace Database\Seeders;

use App\Models\Kawasan;
use App\Models\Kelompok;
use Illuminate\Database\Seeder;

class BksdaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NteSeeder::class);

        // Data Kelompok Binaan
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
            ],
        ];

        foreach ($daftarKelompok as $klp) {
            $kawasan = Kawasan::where('nama_kawasan', $klp['kawasan'])->first();

            if ($kawasan) {
                Kelompok::firstOrCreate(
                    ['sk_kelompok' => $klp['sk']],
                    [
                        'kawasan_id' => $kawasan->id,
                        'tahun_pembinaan' => $klp['tahun'],
                        'nama_kelompok' => $klp['nama'],
                        'nama_ketua' => $klp['ketua'],
                        'no_hp' => $klp['hp'],
                        'jenis_usaha' => $klp['usaha'],
                        'produk_unggulan' => $klp['produk'],
                        'jumlah_anggota' => $klp['anggota'],
                        'jumlah_bantuan' => $klp['bantuan'],
                    ]
                );
            }
        }
    }
}
