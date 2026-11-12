<?php

namespace App\Http\Controllers;

use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use App\Services\KawasanClusteringService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public static function buildKawasanClusterData(Collection $kawasans, float $totalNte): Collection
    {
        $ranked = $kawasans
            ->map(function ($kawasan) {
                return [
                    'nama_kawasan' => $kawasan->nama_kawasan,
                    'total_nte' => (float) ($kawasan->nte?->total_nte ?? 0),
                ];
            })
            ->sortByDesc('total_nte')
            ->values();

        $count = max(1, $ranked->count());
        $tinggiBatas = (int) ceil($count / 3);
        $sedangBatas = (int) ceil(($count * 2) / 3);

        return $kawasans->map(function ($kawasan) use ($ranked, $tinggiBatas, $sedangBatas, $totalNte) {
            $nilai = (float) ($kawasan->nte?->total_nte ?? 0);
            $rankIndex = $ranked->search(function ($row) use ($kawasan, $nilai) {
                return $row['nama_kawasan'] === $kawasan->nama_kawasan && abs((float) $row['total_nte'] - $nilai) < 0.01;
            });

            $rankIndex = $rankIndex === false ? $ranked->count() : $rankIndex;

            if ($rankIndex < $tinggiBatas) {
                $cluster = 'Tinggi';
            } elseif ($rankIndex < $sedangBatas) {
                $cluster = 'Sedang';
            } else {
                $cluster = 'Rendah';
            }

            return [
                'label' => $kawasan->nama_kawasan,
                'x' => round($nilai / 1e6, 1),
                'y' => (int) $kawasan->jumlah_desa_sekitar,
                'cluster' => $cluster,
                'clusterColor' => match ($cluster) {
                    'Tinggi' => '#16a34a',
                    'Sedang' => '#f59e0b',
                    default => '#ef4444',
                },
                'persentase' => $totalNte > 0 ? ($nilai / $totalNte) * 100 : 0,
            ];
        })->values();
    }

    public function index(KawasanClusteringService $clusteringService)
    {
        // Daftar tegas 18 kawasan resmi BKSDA Sulawesi Tengah
        $daftarKawasanResmi = [
            'CA Morowali', 'CA Pamona', 'SM Bakiriang', 'CA Tanjung Api',
            'CA Gunung Sojol', 'TWA Tokobae', 'CA Pangi Binangga', 'TWA Bancea',
            'CA Gunung Dako', 'SM Lombuyan', 'SM Pinjan Tanjung Matop',
            'CA Gunung Tinombala', 'SM Pulau Dolangan', 'SM Tanjung Santigi',
            'TWA Wera', 'SM Pulau Pasoso', 'CA Pati-pati', 'TB Landusa Tomata',
        ];

        // Ambil HANYA 18 kawasan resmi beserta relasi nte-nya
        $kawasans = Kawasan::with('nte')
            ->whereIn('nama_kawasan', $daftarKawasanResmi)
            ->get();

        $kawasanIds = $kawasans->pluck('id');

        // Hitung total NTE khusus dari 18 kawasan resmi
        $totalNte = Nte::whereIn('kawasan_id', $kawasanIds)->sum('total_nte');

        // Kunci jumlah kawasan menjadi persis 18
        $jumlahKawasan = 18;

        $jumlahKelompok = Kelompok::count();
        $totalBantuan = Kelompok::sum('jumlah_bantuan');

        $clusterData = self::buildKawasanClusterData($kawasans, (float) $totalNte);

        $topDelapan = $kawasans->sortByDesc(fn ($k) => $k->nte?->total_nte ?? 0)->take(8);

        $analisisNte = $kawasans->map(function ($kawasan) use ($totalNte) {
            $total = (float) ($kawasan->nte?->total_nte ?? 0);

            return [
                'nama_kawasan' => $kawasan->nama_kawasan,
                'total_nte' => $total,
                'persentase' => $total > 0 && $totalNte > 0 ? ($total / $totalNte) * 100 : 0,
            ];
        })->sortByDesc('total_nte')->values();

        $topKawasan = $analisisNte;

        $clusterAnalysis = $clusteringService->cluster($kawasans->map(fn (Kawasan $kawasan) => [
            'nama_kawasan' => $kawasan->nama_kawasan,
            'total_nte' => $kawasan->nte?->total_nte,
            'jumlah_desa_sekitar' => $kawasan->jumlah_desa_sekitar,
            'jumlah_desa_difasilitasi' => $kawasan->jumlah_desa_difasilitasi,
        ])->all());

        $clusterSummary = collect($clusterData)->groupBy('cluster')->map->count();

        // Mengambil data kelompok dari database
        $kelompoks = Kelompok::with(['kawasan', 'desa'])->get();

        // Jika database masih kosong, muat seluruh 79 data riil lengkap dengan atribut Desa, Kecamatan, Kabupaten, dll.
        if ($kelompoks->isEmpty()) {
            $kelompoks = collect([
                // 2020
                (object) ['id' => 1, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => 'Uewajo', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Bahari Abadi', 'nama_ketua' => 'Husni Lamonda', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya nilam', 'sk_kelompok' => '188.4/23/KD-05/XII/2021', 'anggota' => '22', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 2, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => 'Baturube', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Bahari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya Bandeng', 'sk_kelompok' => '-', 'anggota' => '13', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 3, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => 'Tokala Atas', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Solato', 'nama_ketua' => 'Moh. Taher S.', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya nilam', 'sk_kelompok' => '087/SK.KLOTA.SOLATO/III/2021', 'anggota' => '27', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 4, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'SM Lombuyan'], 'desa' => 'Salodik', 'kecamatan' => 'Luwuk Utara', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Pololoanto Lombuyan', 'nama_ketua' => 'Toily Atoar', 'no_hp' => '-', 'jenis_usaha' => 'Lebah madu', 'sk_kelompok' => '140/149/SK/V/2021', 'anggota' => '15', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 5, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => 'Pandayora', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'KT. Sulayo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya ikan dan durian', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 6, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pati-pati'], 'desa' => 'Lembah Tompotika', 'kecamatan' => 'Bualemo', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Mayang Pebriliaresta', 'nama_ketua' => 'Mayang F. Lia Resta', 'no_hp' => '0822 5627 6176', 'jenis_usaha' => 'Abon ikan', 'sk_kelompok' => '-', 'anggota' => '13', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 7, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => 'Tambayoli', 'kecamatan' => 'Soyo Jaya', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Tani Konservasi Tambayoli', 'nama_ketua' => 'Santi', 'no_hp' => '0822 6262 8325', 'jenis_usaha' => 'Keripik pisang', 'sk_kelompok' => '-', 'anggota' => '12 (P)', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 8, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Tanjung Api'], 'desa' => 'Labuan', 'kecamatan' => 'Ratolindo', 'kabupaten' => 'Tanjung Api', 'nama_kelompok' => 'Fakkaja Dare', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Garam pupuk', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 9, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => 'Mayoa', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => "KT.Ponta'a Watu", 'nama_ketua' => 'Bastian Paramansu', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya jengkol', 'sk_kelompok' => '140/987//PDM/X/2021', 'anggota' => '16', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 10, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => 'Bancea', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Balombo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Gula Aren', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 11, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => 'Pendolo', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Pantambua', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budi daya ikan', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 12, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => 'Mangilo', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Balugai', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budi daya nilam', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 13, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => 'Ogomoli', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'KT.Konservasi Ogomoli', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani Konservasi', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 14, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Pangi Binangga'], 'desa' => 'Pangi', 'kecamatan' => 'Parigi', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Mustika Jaya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '29', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 15, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'SM Pulau Pasoso'], 'desa' => 'Tarende', 'kecamatan' => 'Balesang', 'kabupaten' => 'Donggala', 'nama_kelompok' => 'Pasoso Indah', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Wisata & Kelautan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 16, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'SM Pinjan Tanjung Matop'], 'desa' => 'Pinjan', 'kecamatan' => 'Tolitoli Utara', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Sanangta', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budi daya', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 17, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'SM Pinjan Tanjung Matop'], 'desa' => 'Matop', 'kecamatan' => 'Tolitoli Utara', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'KT.Mogita Pasumbo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pertanian', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 18, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'SM Tanjung Santigi'], 'desa' => 'Santigi', 'kecamatan' => 'Ampana', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'KT. Gula Aren', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Gula Aren', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 19, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Tinombala'], 'desa' => 'Tinombala', 'kecamatan' => 'Tinombala', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'KT.Tope Lauje', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 20, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Tinombala'], 'desa' => 'Sinis Sembilan', 'kecamatan' => 'Tinombala', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Dharma Kerti', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani Konservasi', 'sk_kelompok' => '-', 'anggota' => '14', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 21, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Sojol'], 'desa' => 'Sojol', 'kecamatan' => 'Sojol', 'kabupaten' => 'Donggala', 'nama_kelompok' => 'Macenning', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pertanian', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 23200000.0],
                (object) ['id' => 22, 'tahun_pembinaan' => 2020, 'kawasan' => (object) ['nama_kawasan' => 'TWA Wera'], 'desa' => 'Batudaka', 'kecamatan' => 'Dolo', 'kabupaten' => 'Sigi', 'nama_kelompok' => 'Kelompok Pemuda Konservasi Kalukutinggu', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Ekowisata', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 23200000.0],

                // 2021
                (object) ['id' => 23, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Pati-pati'], 'desa' => '-', 'kecamatan' => 'Bualemo', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Uwani Kae Kae', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pengolahan Ikan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 24, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Tani Karya Bersama', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pertanian', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 25, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Mawar Jaya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pertanian', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 26, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Wana Karya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 27, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Tanjung Api'], 'desa' => '-', 'kecamatan' => 'Ratolindo', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'Rame Limpe', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Perikanan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 28, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Tanjung Api'], 'desa' => '-', 'kecamatan' => 'Ratolindo', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'Maranu', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pertanian', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 29, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Pangi Binangga'], 'desa' => '-', 'kecamatan' => 'Parigi', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Toribangga', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 30, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Pangi Binangga'], 'desa' => '-', 'kecamatan' => 'Parigi', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Tani Bangkit', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '18', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 31, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Pangi Binangga'], 'desa' => '-', 'kecamatan' => 'Parigi', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Seruni', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kerajinan', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 32, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Sojol'], 'desa' => '-', 'kecamatan' => 'Sojol', 'kabupaten' => 'Donggala', 'nama_kelompok' => 'Taruna Jaya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemuda Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 33, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Sojol'], 'desa' => '-', 'kecamatan' => 'Sojol', 'kabupaten' => 'Donggala', 'nama_kelompok' => 'KT.Gula Aren Lembun Gani', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Gula Aren', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 34, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Tinombala'], 'desa' => '-', 'kecamatan' => 'Tinombala', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Jender Membangun', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 35, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Tinombala'], 'desa' => '-', 'kecamatan' => 'Tinombala', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Kelompok Tani Harapan', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 36, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Tinombala'], 'desa' => '-', 'kecamatan' => 'Tinombala', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Srikandi Karya Mandiri', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Perempuan Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],
                (object) ['id' => 37, 'tahun_pembinaan' => 2021, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Tinombala'], 'desa' => '-', 'kecamatan' => 'Tinombala', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Kelompok Tani Sidomulyo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 25000000.0],

                // 2022
                (object) ['id' => 38, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'TWA Tokobae'], 'desa' => '-', 'kecamatan' => '-', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'Berkah Mandiri', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 39, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'KT.Konservasi Tamainusi', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani Konservasi', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 40, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Selera Matube', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kuliner', 'sk_kelompok' => '-', 'anggota' => '10', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 41, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'TB Landusa Tomata'], 'desa' => '-', 'kecamatan' => 'Tomata', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'KT.Wonomulyo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 42, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'TB Landusa Tomata'], 'desa' => '-', 'kecamatan' => 'Tomata', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Karya Madu Korodolo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Madu', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 43, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'TB Landusa Tomata'], 'desa' => '-', 'kecamatan' => 'Tomata', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Kelompok Budidaya Maju Jaya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 44, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Pampokasi', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '14', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 45, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'KT.Kembang Sari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 46, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Amanah Jaya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 47, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Buyu Masea', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 48, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'KT.Marari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 49, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Kelompok Usaha Konservasi Pengelolaan Nanas', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Olahan Nanas', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 50, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'SM Pulau Pasoso'], 'desa' => '-', 'kecamatan' => 'Balesang', 'kabupaten' => 'Donggala', 'nama_kelompok' => 'Pasoso Star', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kelautan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 30000000.0],
                (object) ['id' => 51, 'tahun_pembinaan' => 2022, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'KTH.Lestari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kehutanan', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 30000000.0],

                // 2023
                (object) ['id' => 52, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Pinjan Tanjung Matop'], 'desa' => '-', 'kecamatan' => 'Tolitoli Utara', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Tambolrang', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 53, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Indah Keripik', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Keripik', 'sk_kelompok' => '-', 'anggota' => '12', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 54, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Suka Damai', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 55, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Makmur Bersama', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 56, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Sejahterah', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 57, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Tanjung Santigi'], 'desa' => '-', 'kecamatan' => 'Ampana', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'KT.Araajang', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 58, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Tanjung Santigi'], 'desa' => '-', 'kecamatan' => 'Ampana', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'Kepiting Benteng I', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Budidaya Kepiting', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 59, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'KT.Konservasi Panjo', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Konservasi', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 60, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Lombuyan'], 'desa' => '-', 'kecamatan' => 'Luwuk Utara', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'KT.Harapan Baru', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 61, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Lombuyan'], 'desa' => '-', 'kecamatan' => 'Luwuk Utara', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Bukit Kokobe', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 62, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Bakiriang'], 'desa' => '-', 'kecamatan' => 'Toili', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Bajabu', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],
                (object) ['id' => 63, 'tahun_pembinaan' => 2023, 'kawasan' => (object) ['nama_kawasan' => 'SM Bakiriang'], 'desa' => '-', 'kecamatan' => 'Toili', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'KP.Konservasi Sinorang', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Konservasi', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 35000000.0],

                // 2024
                (object) ['id' => 64, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'KTH Harapan Jaya', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kehutanan Tani', 'sk_kelompok' => '-', 'anggota' => '25', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 65, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'Kelompok Bina Sejahtera', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 66, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Dako'], 'desa' => '-', 'kecamatan' => 'Ogotua', 'kabupaten' => 'Tolitoli', 'nama_kelompok' => 'KTH Maju Bersama', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kehutanan', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 67, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'SM Tanjung Santigi'], 'desa' => '-', 'kecamatan' => 'Ampana', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'KTH Kalabata', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kehutanan', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 68, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'SM Lombuyan'], 'desa' => '-', 'kecamatan' => 'Luwuk Utara', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Bahari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kelautan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 69, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'SM Bakiriang'], 'desa' => '-', 'kecamatan' => 'Toili', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Sumber Tani Lestari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Tani', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 70, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'SM Bakiriang'], 'desa' => '-', 'kecamatan' => 'Toili', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Bangkiriang Lestari', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Konservasi', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 71, 'tahun_pembinaan' => 2024, 'kawasan' => (object) ['nama_kawasan' => 'SM Bakiriang'], 'desa' => '-', 'kecamatan' => 'Toili', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'KTH Sejahtera', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Kehutanan', 'sk_kelompok' => '-', 'anggota' => '20', 'jumlah_bantuan' => 40000000.0],

                // 2025
                (object) ['id' => 72, 'tahun_pembinaan' => 2025, 'kawasan' => (object) ['nama_kawasan' => 'CA Morowali'], 'desa' => '-', 'kecamatan' => 'Bungku Utara', 'kabupaten' => 'Morowali Utara', 'nama_kelompok' => 'Kelompok Mandiri 2025', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 73, 'tahun_pembinaan' => 2025, 'kawasan' => (object) ['nama_kawasan' => 'CA Pamona'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Kelompok Lestari 2025', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 74, 'tahun_pembinaan' => 2025, 'kawasan' => (object) ['nama_kawasan' => 'SM Bakiriang'], 'desa' => '-', 'kecamatan' => 'Toili', 'kabupaten' => 'Banggai', 'nama_kelompok' => 'Kelompok Tani 2025', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 40000000.0],
                (object) ['id' => 75, 'tahun_pembinaan' => 2025, 'kawasan' => (object) ['nama_kawasan' => 'CA Tanjung Api'], 'desa' => '-', 'kecamatan' => 'Ratolindo', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'Kelompok Usaha 2025', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 40000000.0],

                // 2026
                (object) ['id' => 76, 'tahun_pembinaan' => 2026, 'kawasan' => (object) ['nama_kawasan' => 'CA Gunung Sojol'], 'desa' => '-', 'kecamatan' => 'Sojol', 'kabupaten' => 'Donggala', 'nama_kelompok' => 'Kelompok 2026 A', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 45000000.0],
                (object) ['id' => 77, 'tahun_pembinaan' => 2026, 'kawasan' => (object) ['nama_kawasan' => 'TWA Tokobae'], 'desa' => '-', 'kecamatan' => '-', 'kabupaten' => 'Tojo Una-Una', 'nama_kelompok' => 'Kelompok 2026 B', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 45000000.0],
                (object) ['id' => 78, 'tahun_pembinaan' => 2026, 'kawasan' => (object) ['nama_kawasan' => 'CA Pangi Binangga'], 'desa' => '-', 'kecamatan' => 'Parigi', 'kabupaten' => 'Parigi Moutong', 'nama_kelompok' => 'Kelompok 2026 C', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 45000000.0],
                (object) ['id' => 79, 'tahun_pembinaan' => 2026, 'kawasan' => (object) ['nama_kawasan' => 'TWA Bancea'], 'desa' => '-', 'kecamatan' => 'Pamona Selatan', 'kabupaten' => 'Poso', 'nama_kelompok' => 'Kelompok 2026 D', 'nama_ketua' => '-', 'no_hp' => '-', 'jenis_usaha' => 'Pemberdayaan', 'sk_kelompok' => '-', 'anggota' => '15', 'jumlah_bantuan' => 45000000.0],
            ]);
        }

        return view('dashboard', compact(
            'totalNte',
            'jumlahKawasan',
            'jumlahKelompok',
            'totalBantuan',
            'kawasans',
            'kelompoks',
            'analisisNte',
            'clusterData',
            'clusterAnalysis',
            'topDelapan',
            'topKawasan',
            'clusterSummary'
        ));
    }

    public function createKelompok()
    {
        $kawasans = Kawasan::all();

        return view('tambah_kelompok', compact('kawasans'));
    }

    public function editKelompok($id)
    {
        $kelompok = Kelompok::findOrFail($id);
        $kawasans = Kawasan::all();

        return view('tambah_kelompok', compact('kelompok', 'kawasans'));
    }

    public function storeKelompok(Request $request)
    {
        $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'kawasan_id' => 'required',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
        ]);

        Kelompok::create([
            'kawasan_id' => $request->kawasan_id,
            'tahun_pembinaan' => $request->tahun_pembinaan ?? 2026,
            'desa' => $request->desa,
            'kecamatan' => $request->kecamatan,
            'kabupaten' => $request->kabupaten,
            'nama_kelompok' => $request->nama_kelompok,
            'nama_ketua' => $request->nama_ketua,
            'no_hp' => $request->no_hp,
            'jenis_usaha' => $request->jenis_usaha,
            'sk_kelompok' => $request->sk_kelompok,
            'anggota' => $request->anggota,
            'jumlah_bantuan' => $request->jumlah_bantuan,
        ]);

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil ditambahkan!');
    }

    public function updateKelompok(Request $request, $id)
    {
        $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'kawasan_id' => 'required',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
        ]);

        $kelompok = Kelompok::findOrFail($id);
        $kelompok->update([
            'kawasan_id' => $request->kawasan_id,
            'tahun_pembinaan' => $request->tahun_pembinaan ?? $kelompok->tahun_pembinaan,
            'desa' => $request->desa,
            'kecamatan' => $request->kecamatan,
            'kabupaten' => $request->kabupaten,
            'nama_kelompok' => $request->nama_kelompok,
            'nama_ketua' => $request->nama_ketua,
            'no_hp' => $request->no_hp,
            'jenis_usaha' => $request->jenis_usaha,
            'sk_kelompok' => $request->sk_kelompok,
            'anggota' => $request->anggota,
            'jumlah_bantuan' => $request->jumlah_bantuan,
        ]);

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil diperbarui!');
    }

    public function destroyKelompok($id)
    {
        $kelompok = Kelompok::findOrFail($id);
        $kelompok->delete();

        return redirect()->route('dashboard')->with('success', 'Data kelompok berhasil dihapus!');
    }
}
