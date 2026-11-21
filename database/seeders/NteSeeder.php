<?php

namespace Database\Seeders;

use App\Models\Kawasan;
use App\Models\Nte;
use App\Models\NteSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use RuntimeException;

class NteSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{nama_kawasan: string, fungsi_kawasan: string, jumlah_desa_sekitar: int, jumlah_desa_difasilitasi: int, nte_langsung: int, nte_tidak_langsung: int}> $dataKawasan */
        $dataKawasan = [
            ['nama_kawasan' => 'CA Tanjung Api', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 4, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 38600000, 'nte_tidak_langsung' => 46100000],
            ['nama_kawasan' => 'CA Pati-pati', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 2, 'jumlah_desa_difasilitasi' => 2, 'nte_langsung' => 46680000, 'nte_tidak_langsung' => 10000000],
            ['nama_kawasan' => 'CA Pangi Binangga', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 9, 'jumlah_desa_difasilitasi' => 4, 'nte_langsung' => 37500000, 'nte_tidak_langsung' => 4725000],
            ['nama_kawasan' => 'CA Pamona', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 11, 'jumlah_desa_difasilitasi' => 10, 'nte_langsung' => 38250000, 'nte_tidak_langsung' => 546675000],
            ['nama_kawasan' => 'CA Morowali', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 17, 'jumlah_desa_difasilitasi' => 10, 'nte_langsung' => 64830000, 'nte_tidak_langsung' => 119350000],
            ['nama_kawasan' => 'CA Gunung Tinombala', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 19, 'jumlah_desa_difasilitasi' => 4, 'nte_langsung' => 32700000, 'nte_tidak_langsung' => 2520000],
            ['nama_kawasan' => 'CA Gunung Sojol', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 34, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 39900000, 'nte_tidak_langsung' => 7920000],
            ['nama_kawasan' => 'CA Gunung Dako', 'fungsi_kawasan' => 'CA', 'jumlah_desa_sekitar' => 10, 'jumlah_desa_difasilitasi' => 9, 'nte_langsung' => 24600000, 'nte_tidak_langsung' => 9945000],
            ['nama_kawasan' => 'SM Bakiriang', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 12, 'jumlah_desa_difasilitasi' => 11, 'nte_langsung' => 36180000, 'nte_tidak_langsung' => 85440000],
            ['nama_kawasan' => 'SM Lombuyan', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 8, 'jumlah_desa_difasilitasi' => 6, 'nte_langsung' => 32280000, 'nte_tidak_langsung' => 1110000],
            // The PDF's facilitated-village counts are inconsistent for these areas; this NTE correction does not infer replacements.
            ['nama_kawasan' => 'SM Pinjan Tanjung Matop', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 2, 'jumlah_desa_difasilitasi' => 0, 'nte_langsung' => 18000000, 'nte_tidak_langsung' => 9450000],
            ['nama_kawasan' => 'SM Pulau Dolangan', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 1, 'jumlah_desa_difasilitasi' => 1, 'nte_langsung' => 18300000, 'nte_tidak_langsung' => 7875000],
            ['nama_kawasan' => 'SM Pulau Pasoso', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 1, 'jumlah_desa_difasilitasi' => 0, 'nte_langsung' => 15500000, 'nte_tidak_langsung' => 0],
            ['nama_kawasan' => 'SM Tanjung Santigi', 'fungsi_kawasan' => 'SM', 'jumlah_desa_sekitar' => 3, 'jumlah_desa_difasilitasi' => 0, 'nte_langsung' => 15600000, 'nte_tidak_langsung' => 10692000],
            ['nama_kawasan' => 'TWA Bancea', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 2, 'jumlah_desa_difasilitasi' => 2, 'nte_langsung' => 38400000, 'nte_tidak_langsung' => 2187000],
            ['nama_kawasan' => 'TWA Tokobae', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 2, 'jumlah_desa_difasilitasi' => 2, 'nte_langsung' => 43050000, 'nte_tidak_langsung' => 0],
            ['nama_kawasan' => 'TWA Wera', 'fungsi_kawasan' => 'TWA', 'jumlah_desa_sekitar' => 4, 'jumlah_desa_difasilitasi' => 1, 'nte_langsung' => 20400000, 'nte_tidak_langsung' => 0],
            ['nama_kawasan' => 'TB Landusa Tomata', 'fungsi_kawasan' => 'TB', 'jumlah_desa_sekitar' => 5, 'jumlah_desa_difasilitasi' => 3, 'nte_langsung' => 40950000, 'nte_tidak_langsung' => 63500000],
        ];

        $summary = [
            'nte_langsung' => 608320000,
            'nte_tidak_langsung' => 927489000,
            'total_nte' => 1535809000,
        ];
        $detailNteLangsung = array_sum(array_column($dataKawasan, 'nte_langsung'));
        $detailNteTidakLangsung = array_sum(array_column($dataKawasan, 'nte_tidak_langsung'));
        $nteLangsungBelumTeralokasi = $summary['nte_langsung'] - $detailNteLangsung;

        if (
            $nteLangsungBelumTeralokasi < 0
            || $summary['nte_tidak_langsung'] !== $detailNteTidakLangsung
            || $summary['total_nte'] !== $summary['nte_langsung'] + $summary['nte_tidak_langsung']
        ) {
            throw new RuntimeException('Ringkasan NTE tidak konsisten dengan rincian kawasan PDF.');
        }

        $legacyKawasanNames = [
            'CA Saluki',
            'TWA Danau Poso',
            'CA Tanjung Toki',
            'CA Marisa',
            'TWA Morowali',
            'SM Banggai',
            'CA Pulau Tokong Bolotu',
            'TWA Donggala',
            'CA Tokorondo',
            'TWA Cabo',
            'TB Londusa Tomata',
        ];

        Nte::query()
            ->where('tahun', 2025)
            ->whereHas('kawasan', function (Builder $query) use ($legacyKawasanNames): void {
                $query->whereIn('nama_kawasan', $legacyKawasanNames);
            })
            ->delete();

        foreach ($dataKawasan as $item) {
            if ($item['nama_kawasan'] === 'SM Pinjan Tanjung Matop') {
                $legacyKawasan = Kawasan::query()
                    ->where('nama_kawasan', 'SM Pinjan')
                    ->first();
                $canonicalKawasanExists = Kawasan::query()
                    ->where('nama_kawasan', $item['nama_kawasan'])
                    ->exists();

                if ($legacyKawasan !== null && ! $canonicalKawasanExists) {
                    $legacyKawasan->update(['nama_kawasan' => $item['nama_kawasan']]);
                }
            }

            $kawasan = Kawasan::query()->firstOrCreate(
                ['nama_kawasan' => $item['nama_kawasan']],
                [
                    'fungsi_kawasan' => $item['fungsi_kawasan'],
                    'jumlah_desa_sekitar' => $item['jumlah_desa_sekitar'],
                    'jumlah_desa_difasilitasi' => $item['jumlah_desa_difasilitasi'],
                ]
            );
            $nteLangsung = $item['nte_langsung'];
            $nteTidakLangsung = $item['nte_tidak_langsung'];

            Nte::query()->updateOrCreate(
                [
                    'kawasan_id' => $kawasan->id,
                    'tahun' => 2025,
                ],
                [
                    'nte_langsung' => $nteLangsung,
                    'nte_tidak_langsung' => $nteTidakLangsung,
                    'total_nte' => $nteLangsung + $nteTidakLangsung,
                ]
            );
        }

        NteSummary::query()->updateOrCreate(
            ['tahun' => 2025],
            [
                ...$summary,
                'nte_langsung_belum_teralokasi' => $nteLangsungBelumTeralokasi,
            ]
        );
    }
}
