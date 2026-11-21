<?php

namespace App\Http\Controllers;

use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use App\Models\NteSummary;
use App\Services\KawasanClusteringService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private const TAHUN_NTE = 2025;

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

    public function index(KawasanClusteringService $clusteringService): View
    {
        $nteSummary = NteSummary::query()
            ->where('tahun', self::TAHUN_NTE)
            ->first();

        $kawasans = Kawasan::query()
            ->whereHas('nte', fn (Builder $query): Builder => $query->where('tahun', self::TAHUN_NTE))
            ->with(['nte' => function (HasOne $relation): void {
                $relation->where('tahun', self::TAHUN_NTE);
            }])
            ->get();

        $totalNtePerKawasan = Nte::query()
            ->where('tahun', self::TAHUN_NTE)
            ->sum('total_nte');
        $totalNte = (float) $totalNtePerKawasan
            + (float) ($nteSummary?->nte_langsung_belum_teralokasi ?? 0);

        $jumlahKawasan = $kawasans->count();

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

        return view('dashboard', compact(
            'totalNte',
            'nteSummary',
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

    public function groups(KawasanClusteringService $clusteringService): View
    {
        return $this->index($clusteringService);
    }

    public function areas(KawasanClusteringService $clusteringService): View
    {
        return $this->index($clusteringService);
    }

    public function map(): View
    {
        return view('peta-kelompok');
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
            'kawasan_id' => 'required|exists:kawasans,id',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
        ]);

        Kelompok::create([
            'kawasan_id' => $request->kawasan_id,
            'tahun_pembinaan' => $request->tahun_pembinaan ?? 2026,
            'kecamatan' => $request->kecamatan,
            'kabupaten' => $request->kabupaten,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'nama_kelompok' => $request->nama_kelompok,
            'nama_ketua' => $request->nama_ketua,
            'no_hp' => $request->no_hp,
            'jenis_usaha' => $request->jenis_usaha,
            'sk_kelompok' => $request->sk_kelompok,
            'jumlah_anggota' => $request->input('jumlah_anggota', 0),
            'jumlah_bantuan' => $request->jumlah_bantuan,
        ]);

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil ditambahkan!');
    }

    public function updateKelompok(Request $request, $id)
    {
        $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'kawasan_id' => 'required|exists:kawasans,id',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
        ]);

        $kelompok = Kelompok::findOrFail($id);
        $kelompok->update([
            'kawasan_id' => $request->kawasan_id,
            'tahun_pembinaan' => $request->tahun_pembinaan ?? $kelompok->tahun_pembinaan,
            'kecamatan' => $request->kecamatan,
            'kabupaten' => $request->kabupaten,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'nama_kelompok' => $request->nama_kelompok,
            'nama_ketua' => $request->nama_ketua,
            'no_hp' => $request->no_hp,
            'jenis_usaha' => $request->jenis_usaha,
            'sk_kelompok' => $request->sk_kelompok,
            'jumlah_anggota' => $request->input('jumlah_anggota', $kelompok->jumlah_anggota),
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
