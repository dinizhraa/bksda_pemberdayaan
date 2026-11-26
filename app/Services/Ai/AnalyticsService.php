<?php

namespace App\Services\Ai;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * @return array{years: list<int>, kawasans: Collection<int, Kawasan>, desas: Collection<int, Desa>, kelompoks: Collection<int, Kelompok>}
     */
    public function filterOptions(): array
    {
        return [
            'years' => Kelompok::query()
                ->whereNotNull('tahun_pembinaan')
                ->distinct()
                ->orderByDesc('tahun_pembinaan')
                ->pluck('tahun_pembinaan')
                ->map(fn ($year): int => (int) $year)
                ->all(),
            'kawasans' => Kawasan::query()->orderBy('nama_kawasan')->get(['id', 'nama_kawasan']),
            'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'kelompoks' => Kelompok::query()->orderBy('nama_kelompok')->get(['id', 'nama_kelompok']),
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return array<string, mixed>
     */
    public function metrics(array $filters = []): array
    {
        $groups = $this->query($filters);
        $summary = (clone $groups)
            ->select([])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status_kelompok = ? THEN 1 ELSE 0 END) AS active', ['Aktif'])
            ->selectRaw('SUM(CASE WHEN status_kelompok = ? THEN 1 ELSE 0 END) AS inactive', ['Tidak Aktif'])
            ->selectRaw('SUM(CASE WHEN status_kelompok IS NULL OR TRIM(status_kelompok) = ? THEN 1 ELSE 0 END) AS unknown_status', [''])
            ->selectRaw('SUM(CASE WHEN tahun_monitoring IS NULL THEN 1 ELSE 0 END) AS unmonitored')
            ->selectRaw("SUM(CASE WHEN catatan_kebutuhan_hasil_monev IS NOT NULL AND TRIM(catatan_kebutuhan_hasil_monev) <> '' THEN 1 ELSE 0 END) AS monev_records")
            ->selectRaw('COALESCE(SUM(jumlah_anggota_laki_laki), 0) AS male_members')
            ->selectRaw('COALESCE(SUM(jumlah_anggota_perempuan), 0) AS female_members')
            ->selectRaw('COALESCE(SUM(jumlah_bantuan), 0) AS total_aid')
            ->first();

        $byStatus = $this->groupCounts($groups, 'status_kelompok');
        $byKawasan = $this->groupByLocation($groups, 'kawasans', 'kawasan_id', 'nama_kawasan', null);
        $byDesa = $this->groupByLocation($groups, 'desas', 'desa_id', 'nama_desa');
        $byBusiness = $this->groupByBusiness($groups);
        $byMonitoringYear = (clone $groups)
            ->whereNotNull('tahun_monitoring')
            ->select('tahun_monitoring')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('tahun_monitoring')
            ->orderBy('tahun_monitoring')
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->tahun_monitoring,
                'value' => (int) $row->total,
            ])
            ->all();
        $byFormationYear = (clone $groups)
            ->whereNotNull('tahun_pembentukan')
            ->select('tahun_pembentukan')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('tahun_pembentukan')
            ->orderBy('tahun_pembentukan')
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->tahun_pembentukan,
                'value' => (int) $row->total,
            ])
            ->all();

        $nteYear = Nte::query()
            ->when(isset($filters['kawasan_id']), fn (Builder $query): Builder => $query->where('kawasan_id', $filters['kawasan_id']))
            ->max('tahun');
        $nteByKawasan = $nteYear === null
            ? []
            : Nte::query()
                ->where('tahun', $nteYear)
                ->when(isset($filters['kawasan_id']), fn (Builder $query): Builder => $query->where('kawasan_id', $filters['kawasan_id']))
                ->with('kawasan:id,nama_kawasan')
                ->get(['kawasan_id', 'total_nte'])
                ->map(fn (Nte $nte): array => [
                    'label' => $nte->kawasan?->nama_kawasan ?? 'Belum diisi',
                    'value' => (float) $nte->total_nte,
                ])
                ->all();

        return [
            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'active' => (int) ($summary->active ?? 0),
                'inactive' => (int) ($summary->inactive ?? 0),
                'unknown_status' => (int) ($summary->unknown_status ?? 0),
                'unmonitored' => (int) ($summary->unmonitored ?? 0),
                'monev_records' => (int) ($summary->monev_records ?? 0),
                'male_members' => (int) ($summary->male_members ?? 0),
                'female_members' => (int) ($summary->female_members ?? 0),
                'total_aid' => (float) ($summary->total_aid ?? 0),
            ],
            'charts' => [
                'status' => $byStatus,
                'kawasan' => $byKawasan,
                'desa' => $byDesa,
                'monitoring_year' => $byMonitoringYear,
                'gender' => [
                    ['label' => 'Laki-laki', 'value' => (int) ($summary->male_members ?? 0)],
                    ['label' => 'Perempuan', 'value' => (int) ($summary->female_members ?? 0)],
                ],
                'business' => $byBusiness,
                'formation_year' => $byFormationYear,
                'nte_year' => $nteYear,
                'nte_by_kawasan' => $nteByKawasan,
            ],
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     */
    public function query(array $filters = []): Builder
    {
        return Kelompok::query()
            ->when(isset($filters['year']), fn (Builder $query): Builder => $query->where('kelompoks.tahun_pembinaan', $filters['year']))
            ->when(isset($filters['from_year']), fn (Builder $query): Builder => $query->where('kelompoks.tahun_pembinaan', '>=', $filters['from_year']))
            ->when(isset($filters['to_year']), fn (Builder $query): Builder => $query->where('kelompoks.tahun_pembinaan', '<=', $filters['to_year']))
            ->when(isset($filters['kawasan_id']), fn (Builder $query): Builder => $query->where('kelompoks.kawasan_id', $filters['kawasan_id']))
            ->when(isset($filters['desa_id']), fn (Builder $query): Builder => $query->where('kelompoks.desa_id', $filters['desa_id']))
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('kelompoks.status_kelompok', $filters['status']))
            ->when(isset($filters['kelompok_id']), fn (Builder $query): Builder => $query->where('kelompoks.id', $filters['kelompok_id']));
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return array<string, mixed>
     */
    public function reportData(array $filters, string $type, DataSanitizer $sanitizer): array
    {
        $metrics = $this->metrics($filters);
        $records = [];

        if (in_array($type, ['groups', 'monev', 'needs', 'aid'], true)) {
            $query = $this->query($filters)
                ->with(['kawasan:id,nama_kawasan', 'desa:id,nama_desa'])
                ->select([
                    'id',
                    'kawasan_id',
                    'desa_id',
                    'nama_kelompok',
                    'status_kelompok',
                    'tahun_pembinaan',
                    'tahun_pembentukan',
                    'tahun_monitoring',
                    'jenis_usaha',
                    'jumlah_anggota',
                    'jumlah_anggota_laki_laki',
                    'jumlah_anggota_perempuan',
                    'jumlah_bantuan',
                    'catatan_kebutuhan_hasil_monev',
                ]);

            if (in_array($type, ['monev', 'needs'], true)) {
                $query->whereNotNull('catatan_kebutuhan_hasil_monev')
                    ->whereRaw("TRIM(catatan_kebutuhan_hasil_monev) <> ''");
            }

            $rows = $query
                ->orderByDesc('jumlah_bantuan')
                ->limit(31)
                ->get();

            $records = $rows->take(30)->map(fn (Kelompok $kelompok): array => [
                'nama_kelompok' => $kelompok->nama_kelompok,
                'kawasan' => $kelompok->kawasan?->nama_kawasan,
                'desa' => $kelompok->desa?->nama_desa,
                'status' => $kelompok->status_kelompok,
                'tahun_pembinaan' => $kelompok->tahun_pembinaan,
                'tahun_pembentukan' => $kelompok->tahun_pembentukan,
                'tahun_monitoring' => $kelompok->tahun_monitoring,
                'jenis_usaha' => $kelompok->jenis_usaha,
                'jumlah_anggota' => $kelompok->jumlah_anggota,
                'jumlah_anggota_laki_laki' => $kelompok->jumlah_anggota_laki_laki,
                'jumlah_anggota_perempuan' => $kelompok->jumlah_anggota_perempuan,
                'jumlah_bantuan' => (float) $kelompok->jumlah_bantuan,
                ...($type === 'monev' || $type === 'needs'
                    ? ['catatan_monev' => $sanitizer->redactContactDetails($kelompok->catatan_kebutuhan_hasil_monev)]
                    : []),
            ])->all();
        }

        return [
            'summary' => $metrics['summary'],
            'charts' => $metrics['charts'],
            'records' => $records,
            'records_truncated' => $rows->count() > 30,
            'monev_record_count' => $metrics['summary']['monev_records'],
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return list<array{label: string, value: int}>
     */
    private function groupCounts(Builder $query, string $column): array
    {
        return (clone $query)
            ->select($column)
            ->selectRaw('COUNT(*) AS total')
            ->groupBy($column)
            ->get()
            ->map(fn (object $row): array => [
                'label' => filled($row->{$column}) ? (string) $row->{$column} : 'Belum diisi',
                'value' => (int) $row->total,
            ])
            ->groupBy('label')
            ->map(fn (Collection $rows, string $label): array => [
                'label' => $label,
                'value' => (int) $rows->sum('value'),
            ])
            ->sortByDesc('value')
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return list<array{label: string, value: int, aid: float}>
     */
    private function groupByLocation(
        Builder $query,
        string $table,
        string $foreignKey,
        string $nameColumn,
        ?int $limit = 12,
    ): array {
        return (clone $query)
            ->leftJoin($table, "kelompoks.$foreignKey", '=', "$table.id")
            ->select([])
            ->selectRaw("COALESCE(NULLIF($table.$nameColumn, ''), ?) AS label, COUNT(*) AS total, COALESCE(SUM(kelompoks.jumlah_bantuan), 0) AS aid", ['Belum diisi'])
            ->groupBy("$table.id", "$table.$nameColumn")
            ->orderByDesc('total')
            ->when($limit !== null, fn (Builder $query): Builder => $query->limit($limit))
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'value' => (int) $row->total,
                'aid' => (float) $row->aid,
            ])
            ->all();
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return list<array{label: string, value: int, aid: float}>
     */
    private function groupByBusiness(Builder $query): array
    {
        return (clone $query)
            ->select([])
            ->selectRaw("COALESCE(NULLIF(jenis_usaha, ''), ?) AS label, COUNT(*) AS total, COALESCE(SUM(jumlah_bantuan), 0) AS aid", ['Belum diisi'])
            ->groupBy('label')
            ->orderByDesc('aid')
            ->limit(12)
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'value' => (int) $row->total,
                'aid' => (float) $row->aid,
            ])
            ->all();
    }
}
