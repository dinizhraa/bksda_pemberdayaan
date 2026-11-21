<?php

namespace App\Http\Controllers;

use App\Models\Desa;
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
use Illuminate\Support\Facades\DB;

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
        $kelompoks = Kelompok::query()
            ->with(['kawasan', 'desa'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $locations = $kelompoks->map(fn (Kelompok $kelompok): array => [
            'nama_kelompok' => $kelompok->nama_kelompok,
            'status_kelompok' => $kelompok->status_kelompok,
            'jenis_usaha' => $kelompok->jenis_usaha,
            'tahun_pembinaan' => $kelompok->tahun_pembinaan,
            'kawasan' => $kelompok->kawasan?->nama_kawasan,
            'desa' => $kelompok->desa?->nama_desa,
            'kecamatan' => $kelompok->kecamatan ?? $kelompok->desa?->kecamatan,
            'kabupaten' => $kelompok->kabupaten ?? $kelompok->desa?->kabupaten,
            'latitude' => (float) $kelompok->latitude,
            'longitude' => (float) $kelompok->longitude,
            'jumlah_anggota_laki_laki' => $kelompok->jumlah_anggota_laki_laki,
            'jumlah_anggota_perempuan' => $kelompok->jumlah_anggota_perempuan,
            'detail_url' => route('kelompok.show', $kelompok),
        ]);

        return view('peta-kelompok', compact('locations'));
    }

    public function showKelompok(Kelompok $kelompok): View
    {
        $kelompok->load(['kawasan', 'desa']);

        return view('detail_kelompok', compact('kelompok'));
    }

    public function editKelompok($id)
    {
        $kelompok = Kelompok::findOrFail($id);
        $kawasans = Kawasan::all();

        return view('tambah_kelompok', compact('kelompok', 'kawasans'));
    }

    public function storeKelompok(Request $request)
    {
        $validated = $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'status_kelompok' => 'required|in:Aktif,Tidak Aktif',
            'kawasan_id' => 'required|exists:kawasans,id',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
            'nama_desa' => 'required|string|max:255',
            'tahun_pembentukan' => 'nullable|integer|between:1000,9999',
            'tahun_monitoring' => 'nullable|integer|between:1000,9999',
            'catatan_kebutuhan_hasil_monev' => 'nullable|string|max:10000',
            'kecamatan' => 'nullable|string|max:255',
            'kabupaten' => 'nullable|string|max:255',
            'sk_kelompok' => 'nullable|string|max:255',
            'jumlah_anggota_laki_laki' => 'required|integer|min:0',
            'jumlah_anggota_perempuan' => 'required|integer|min:0',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $desa = $this->resolveOrCreateDesa(
                trim($validated['nama_desa']),
                (int) $validated['kawasan_id'],
                $validated['kecamatan'] ?? null,
                $validated['kabupaten'] ?? null,
            );

            Kelompok::create([
                'kawasan_id' => $validated['kawasan_id'],
                'desa_id' => $desa->id,
                'tahun_pembinaan' => $request->tahun_pembinaan ?? 2026,
                'kecamatan' => $validated['kecamatan'] ?? null,
                'kabupaten' => $validated['kabupaten'] ?? null,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'nama_kelompok' => $validated['nama_kelompok'],
                'status_kelompok' => $validated['status_kelompok'],
                'nama_ketua' => $validated['nama_ketua'],
                'tahun_pembentukan' => $validated['tahun_pembentukan'] ?? null,
                'tahun_monitoring' => $validated['tahun_monitoring'] ?? null,
                'catatan_kebutuhan_hasil_monev' => $validated['catatan_kebutuhan_hasil_monev'] ?? null,
                'no_hp' => $request->no_hp,
                'jenis_usaha' => $validated['jenis_usaha'],
                'sk_kelompok' => $validated['sk_kelompok'] ?? null,
                'jumlah_anggota_laki_laki' => $validated['jumlah_anggota_laki_laki'],
                'jumlah_anggota_perempuan' => $validated['jumlah_anggota_perempuan'],
                'jumlah_anggota' => (int) $validated['jumlah_anggota_laki_laki']
                    + (int) $validated['jumlah_anggota_perempuan'],
                'jumlah_bantuan' => $validated['jumlah_bantuan'],
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil ditambahkan!');
    }

    public function updateKelompok(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'status_kelompok' => 'required|in:Aktif,Tidak Aktif',
            'kawasan_id' => 'required|exists:kawasans,id',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
            'nama_desa' => 'required|string|max:255',
            'tahun_pembentukan' => 'nullable|integer|between:1000,9999',
            'tahun_monitoring' => 'nullable|integer|between:1000,9999',
            'catatan_kebutuhan_hasil_monev' => 'nullable|string|max:10000',
            'kecamatan' => 'nullable|string|max:255',
            'kabupaten' => 'nullable|string|max:255',
            'sk_kelompok' => 'nullable|string|max:255',
            'jumlah_anggota_laki_laki' => 'nullable|integer|min:0|required_with:jumlah_anggota_perempuan',
            'jumlah_anggota_perempuan' => 'nullable|integer|min:0|required_with:jumlah_anggota_laki_laki',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
        ]);

        $kelompok = Kelompok::findOrFail($id);
        $memberCounts = [];

        if (($validated['jumlah_anggota_laki_laki'] ?? null) !== null
            && ($validated['jumlah_anggota_perempuan'] ?? null) !== null) {
            $memberCounts = [
                'jumlah_anggota_laki_laki' => $validated['jumlah_anggota_laki_laki'],
                'jumlah_anggota_perempuan' => $validated['jumlah_anggota_perempuan'],
                'jumlah_anggota' => (int) $validated['jumlah_anggota_laki_laki']
                    + (int) $validated['jumlah_anggota_perempuan'],
            ];
        }

        DB::transaction(function () use ($request, $validated, $kelompok, $memberCounts): void {
            $desa = $this->resolveOrCreateDesa(
                trim($validated['nama_desa']),
                (int) $validated['kawasan_id'],
                $validated['kecamatan'] ?? null,
                $validated['kabupaten'] ?? null,
            );

            $kelompok->update([
                'kawasan_id' => $validated['kawasan_id'],
                'desa_id' => $desa->id,
                'tahun_pembinaan' => $request->tahun_pembinaan ?? $kelompok->tahun_pembinaan,
                'kecamatan' => $validated['kecamatan'] ?? null,
                'kabupaten' => $validated['kabupaten'] ?? null,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'nama_kelompok' => $validated['nama_kelompok'],
                'status_kelompok' => $validated['status_kelompok'],
                'nama_ketua' => $validated['nama_ketua'],
                'tahun_pembentukan' => $validated['tahun_pembentukan'] ?? null,
                'tahun_monitoring' => $validated['tahun_monitoring'] ?? null,
                'catatan_kebutuhan_hasil_monev' => $validated['catatan_kebutuhan_hasil_monev'] ?? null,
                'no_hp' => $request->no_hp,
                'jenis_usaha' => $validated['jenis_usaha'],
                'sk_kelompok' => $validated['sk_kelompok'] ?? null,
                'jumlah_bantuan' => $validated['jumlah_bantuan'],
                ...$memberCounts,
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil diperbarui!');
    }

    private function resolveOrCreateDesa(string $namaDesa, int $kawasanId, ?string $kecamatan, ?string $kabupaten): Desa
    {
        $desa = Desa::query()
            ->where('nama_desa', $namaDesa)
            ->where(function (Builder $query) use ($kawasanId): void {
                $query->where('kawasan_id', $kawasanId)
                    ->orWhereHas('kawasans', fn (Builder $relation): Builder => $relation->whereKey($kawasanId));
            })
            ->first();

        if ($desa === null) {
            $desa = Desa::query()->create([
                'kawasan_id' => $kawasanId,
                'nama_desa' => $namaDesa,
                'kecamatan' => $kecamatan,
                'kabupaten' => $kabupaten,
            ]);
        }

        $desa->kawasans()->syncWithoutDetaching([$kawasanId]);

        return $desa;
    }

    public function destroyKelompok($id)
    {
        $kelompok = Kelompok::findOrFail($id);
        $kelompok->delete();

        return redirect()->route('dashboard')->with('success', 'Data kelompok berhasil dihapus!');
    }
}
