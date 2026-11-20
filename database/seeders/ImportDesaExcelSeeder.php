<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kawasan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportDesaExcelSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/desa_import.json');

        if (! file_exists($path)) {
            throw new \RuntimeException('File desa_import.json tidak ditemukan.');
        }

        $data = json_decode(file_get_contents($path), true);

        if (! is_array($data) || count($data) !== 140) {
            throw new \RuntimeException('Jumlah atau format data desa tidak sesuai.');
        }

        DB::transaction(function () use ($data) {
            $kawasanMap = Kawasan::pluck('id', 'nama_kawasan');

            $totalDesa = 0;
            $totalRelasi = 0;

            foreach ($data as $item) {
                if (empty($item['kode_desa']) || empty($item['nama_desa'])) {
                    throw new \RuntimeException('Ada data desa tanpa kode atau nama.');
                }

                if (empty($item['kawasans']) || ! is_array($item['kawasans'])) {
                    throw new \RuntimeException(
                        'Kawasan desa '.$item['nama_desa'].' belum tersedia.'
                    );
                }

                $kawasanIds = [];

                foreach ($item['kawasans'] as $namaKawasan) {
                    if (! isset($kawasanMap[$namaKawasan])) {
                        throw new \RuntimeException(
                            'Kawasan tidak ditemukan: '.$namaKawasan
                        );
                    }

                    $kawasanIds[] = $kawasanMap[$namaKawasan];
                }

                $kawasanIds = array_values(array_unique($kawasanIds));

                $desa = Desa::updateOrCreate(
                    ['kode_desa' => $item['kode_desa']],
                    [
                        'kawasan_id' => $kawasanIds[0],
                        'nama_desa' => $item['nama_desa'],
                        'kecamatan' => $item['kecamatan'],
                        'kabupaten' => $item['kabupaten'],
                        'jumlah_penduduk' => $item['jumlah_penduduk'],
                        'jumlah_penutupan_kk' => $item['jumlah_penutupan_kk'],
                    ]
                );

                $desa->kawasans()->sync($kawasanIds);

                $totalDesa++;
                $totalRelasi += count($kawasanIds);
            }

            dump([
                'desa_diproses' => $totalDesa,
                'relasi_diproses' => $totalRelasi,
                'total_desa_database' => Desa::count(),
                'total_relasi_database' => DB::table('desa_kawasan')->count(),
            ]);
        });
    }
}
