<?php

namespace App\Services\Ai;

use App\Models\Kelompok;
use App\Models\Nte;
use App\Models\NteSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ChatContextService
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly DataSanitizer $sanitizer,
    ) {}

    public function fastAnswer(string $question): ?string
    {
        $normalized = Str::lower(Str::squish($question));

        if (! Str::contains($normalized, ['berapa', 'jumlah', 'total', 'banyak', 'hitung'])
            || Str::contains($normalized, [
                ' berdasarkan ',
                ' di kawasan ',
                ' di desa ',
                ' pada tahun ',
                ' tahun ',
                ' per kawasan',
                ' per desa',
                ' per status',
                ' per tahun',
                ' paling banyak',
                ' terbanyak',
            ])) {
            return null;
        }

        $topicCount = count(array_filter([
            Str::contains($normalized, 'anggota'),
            Str::contains($normalized, ['bantuan', 'dana']),
            Str::contains($normalized, ['aktif', 'tidak aktif']),
            Str::contains($normalized, ['monitor', 'monev']),
            Str::contains($normalized, 'desa'),
            Str::contains($normalized, 'kawasan'),
        ]));

        if ($topicCount > 1) {
            return null;
        }

        if (Str::contains($normalized, 'anggota')) {
            $maleMembers = (int) Kelompok::query()->sum('jumlah_anggota_laki_laki');
            $femaleMembers = (int) Kelompok::query()->sum('jumlah_anggota_perempuan');

            if (Str::contains($normalized, ['laki-laki', 'laki laki', 'pria'])
                && Str::contains($normalized, ['perempuan', 'wanita'])) {
                return 'Jumlah anggota tercatat '.self::formatCount($maleMembers + $femaleMembers)
                    .' orang: '.self::formatCount($maleMembers).' laki-laki dan '
                    .self::formatCount($femaleMembers).' perempuan.';
            }

            if (Str::contains($normalized, ['perempuan', 'wanita'])) {
                return 'Jumlah anggota perempuan tercatat '.self::formatCount($femaleMembers).' orang.';
            }

            if (Str::contains($normalized, ['laki-laki', 'laki laki', 'pria'])) {
                return 'Jumlah anggota laki-laki tercatat '.self::formatCount($maleMembers).' orang.';
            }

            $totalMembers = (int) Kelompok::query()->sum('jumlah_anggota');

            return 'Jumlah anggota tercatat '.self::formatCount($totalMembers).' orang.';
        }

        if (Str::contains($normalized, ['bantuan', 'dana'])) {
            $totalAid = (float) Kelompok::query()->sum('jumlah_bantuan');

            return 'Total bantuan yang tercatat sebesar Rp'.self::formatCount($totalAid).'.';
        }

        if (Str::contains($normalized, ['monitor', 'monev'])) {
            if (! Str::contains($normalized, ['belum dimonitor', 'belum dimonitoring', 'belum dimonev'])) {
                return null;
            }

            $unmonitoredGroups = Kelompok::query()->whereNull('tahun_monitoring')->count();

            return 'Jumlah kelompok yang belum dimonitor tercatat '.self::formatCount($unmonitoredGroups).' kelompok.';
        }

        if (Str::contains($normalized, 'kelompok')
            && Str::contains($normalized, ['aktif dan tidak aktif', 'tidak aktif dan aktif', 'aktif maupun tidak aktif'])) {
            $activeGroups = Kelompok::query()->where('status_kelompok', 'Aktif')->count();
            $inactiveGroups = Kelompok::query()->where('status_kelompok', 'Tidak Aktif')->count();

            return 'Jumlah kelompok tercatat '.self::formatCount($activeGroups + $inactiveGroups)
                .': '.self::formatCount($activeGroups).' aktif dan '
                .self::formatCount($inactiveGroups).' tidak aktif.';
        }

        if (Str::contains($normalized, 'kelompok') && Str::contains($normalized, 'tidak aktif')) {
            $inactiveGroups = Kelompok::query()->where('status_kelompok', 'Tidak Aktif')->count();

            return 'Jumlah kelompok tidak aktif tercatat '.self::formatCount($inactiveGroups).' kelompok.';
        }

        if (Str::contains($normalized, 'kelompok') && Str::contains($normalized, 'aktif')) {
            $activeGroups = Kelompok::query()->where('status_kelompok', 'Aktif')->count();

            return 'Jumlah kelompok aktif tercatat '.self::formatCount($activeGroups).' kelompok.';
        }

        if (Str::contains($normalized, 'desa')) {
            $villages = Str::contains($normalized, ['memiliki kelompok', 'dengan kelompok'])
                ? Kelompok::query()->whereNotNull('desa_id')->distinct()->count('desa_id')
                : Desa::query()->count();

            return 'Jumlah desa'.(Str::contains($normalized, ['memiliki kelompok', 'dengan kelompok']) ? ' dengan kelompok binaan' : '')
                .' tercatat '.self::formatCount($villages).' desa.';
        }

        if (Str::contains($normalized, 'kawasan')) {
            $areas = Kawasan::query()->count();

            return 'Jumlah kawasan tercatat '.self::formatCount($areas).' kawasan.';
        }

        if (Str::contains($normalized, [
            'berapa kelompok',
            'berapa banyak kelompok',
            'ada berapa kelompok',
            'jumlah kelompok',
            'total kelompok',
        ])) {
            $groups = Kelompok::query()->count();

            return 'Jumlah kelompok binaan tercatat '.self::formatCount($groups).' kelompok.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function forQuestion(string $question, string $previousQuestion = ''): array
    {
        $metrics = $this->analytics->metrics();
        $summary = Arr::only($metrics['summary'], [
            'total',
            'active',
            'inactive',
            'unknown_status',
            'unmonitored',
            'monev_records',
            'male_members',
            'female_members',
            'total_aid',
        ]);
        $normalized = $this->intentText($question, $previousQuestion);
        $context = ['ringkasan_database' => $summary];

        if (Str::contains($normalized, ['ringkasan', 'pemberdayaan', 'rekap', 'gambaran umum'])) {
            $context['kelompok_per_status'] = $metrics['charts']['status'];
            $context['kelompok_per_kawasan'] = $metrics['charts']['kawasan'];
            $context['kelompok_per_desa'] = $metrics['charts']['desa'];
            $context['kelompok_per_tahun_pembinaan'] = $this->groupCountsByYear('tahun_pembinaan');
            $context['kelompok_per_tahun_pembentukan'] = $metrics['charts']['formation_year'];
        }

        if (Str::contains($normalized, ['kawasan', 'desa', 'sebaran', 'terbanyak'])) {
            $context['kelompok_per_kawasan'] = $metrics['charts']['kawasan'];
            $context['kelompok_per_desa'] = $metrics['charts']['desa'];
            $context['jumlah_desa_dengan_kelompok'] = $this->analytics->query()
                ->whereNotNull('desa_id')
                ->distinct()
                ->count('desa_id');
            $context['nte_per_kawasan'] = [
                'tahun' => $metrics['charts']['nte_year'],
                'data' => $metrics['charts']['nte_by_kawasan'],
            ];
        }

        if (Str::contains($normalized, ['bantuan', 'dana', 'usaha'])) {
            $context['bantuan_per_jenis_usaha'] = $metrics['charts']['business'];
            $context['bantuan_per_kawasan'] = $metrics['charts']['kawasan'];
            $context['total_bantuan_rupiah'] = $summary['total_aid'];
            $context['kelompok_dengan_bantuan_terbesar'] = $this->groupRows(
                $this->analytics->query()->orderByDesc('jumlah_bantuan'),
            );
        }

        if (Str::contains($normalized, ['tahun', 'perkembangan', 'pembentukan'])) {
            $context['kelompok_per_tahun_pembinaan'] = $this->groupCountsByYear('tahun_pembinaan');
            $context['kelompok_per_tahun_pembentukan'] = $metrics['charts']['formation_year'];
        }

        if (Str::contains($normalized, ['konservasi', 'nte', 'nilai ekonomi', 'bidang', 'langsung', 'tidak langsung'])) {
            $ntePerYear = Nte::query()
                ->select('tahun')
                ->selectRaw('COUNT(*) AS jumlah_record_kawasan')
                ->selectRaw('COALESCE(SUM(nte_langsung), 0) AS nte_langsung')
                ->selectRaw('COALESCE(SUM(nte_tidak_langsung), 0) AS nte_tidak_langsung')
                ->selectRaw('COALESCE(SUM(total_nte), 0) AS total_nte')
                ->groupBy('tahun')
                ->orderBy('tahun')
                ->get()
                ->map(fn (object $row): array => [
                    'tahun' => (int) $row->tahun,
                    'jumlah_record_kawasan' => (int) $row->jumlah_record_kawasan,
                    'nte_langsung' => (float) $row->nte_langsung,
                    'nte_tidak_langsung' => (float) $row->nte_tidak_langsung,
                    'total_nte' => (float) $row->total_nte,
                ])
                ->all();
            $nteSummaries = NteSummary::query()
                ->orderBy('tahun')
                ->get([
                    'tahun',
                    'nte_langsung',
                    'nte_tidak_langsung',
                    'total_nte',
                    'nte_langsung_belum_teralokasi',
                ])
                ->map(fn (NteSummary $row): array => [
                    'tahun' => (int) $row->tahun,
                    'nte_langsung' => (float) $row->nte_langsung,
                    'nte_tidak_langsung' => (float) $row->nte_tidak_langsung,
                    'total_nte' => (float) $row->total_nte,
                    'nte_langsung_belum_teralokasi' => (float) $row->nte_langsung_belum_teralokasi,
                ])
                ->all();

            $context['data_konservasi_nte'] = [
                'jumlah_record_kawasan_tahun' => Nte::query()->count(),
                'per_tahun' => $ntePerYear,
                'ringkasan_resmi_per_tahun' => $nteSummaries,
            ];

            if ($ntePerYear === [] && $nteSummaries === []) {
                $context['pemberitahuan_konservasi'] = 'Belum ada data NTE tersimpan di database.';
            }
        }

        if (Str::contains($normalized, ['status', 'aktif', 'tidak aktif'])) {
            $context['kelompok_per_status'] = $metrics['charts']['status'];
            $statusQuery = $this->analytics->query();

            if (Str::contains($normalized, 'tidak aktif') && ! Str::contains($normalized, 'aktif dan tidak aktif')) {
                $statusQuery->where('status_kelompok', 'Tidak Aktif');
            } elseif (Str::contains($normalized, 'masih aktif') || Str::contains($normalized, 'yang aktif')) {
                $statusQuery->where('status_kelompok', 'Aktif');
            }

            $context['daftar_kelompok_relevan'] = $this->groupRows($statusQuery);
        }

        if (Str::contains($normalized, ['monitor', 'monev'])) {
            $context['kelompok_belum_dimonitoring'] = $this->groupRows(
                $this->analytics->query()->whereNull('tahun_monitoring'),
            );
        }

        if (Str::contains($normalized, ['pendamping', 'kebutuhan', 'kendala', 'monev'])) {
            $groupsWithNotes = $this->analytics->query()
                ->whereNotNull('catatan_kebutuhan_hasil_monev')
                ->whereRaw("TRIM(catatan_kebutuhan_hasil_monev) <> ''");

            $context['kelompok_dengan_catatan_monev'] = $this->groupRows($groupsWithNotes, includeMonevNotes: true);

            if ($context['kelompok_dengan_catatan_monev']['rows'] === []) {
                $context['pemberitahuan'] = 'Belum ada catatan kebutuhan atau hasil Monev pada database.';
            }
        }

        return $context;
    }

    private function intentText(string $question, string $previousQuestion): string
    {
        $normalized = Str::lower(Str::squish($question));
        $topicTerms = [
            'kelompok',
            'binaan',
            'kawasan',
            'desa',
            'status',
            'aktif',
            'monitor',
            'monev',
            'anggota',
            'laki',
            'perempuan',
            'bantuan',
            'dana',
            'usaha',
            'tahun',
            'pembinaan',
            'pembentukan',
            'konservasi',
            'nte',
            'nilai ekonomi',
            'bidang',
            'pemberdayaan',
            'data',
        ];

        if ($previousQuestion !== '' && ! Str::contains($normalized, $topicTerms)) {
            return $normalized.' '.Str::lower($previousQuestion);
        }

        return $normalized;
    }

    private static function formatCount(int|float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * @return list<array{tahun: int, jumlah_kelompok: int}>
     */
    private function groupCountsByYear(string $column): array
    {
        return $this->analytics->query()
            ->whereNotNull($column)
            ->select($column)
            ->selectRaw('COUNT(*) AS total')
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->map(fn (object $row): array => [
                'tahun' => (int) $row->{$column},
                'jumlah_kelompok' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return array{rows: list<array<string, int|float|string|null>>, truncated: bool}
     */
    private function groupRows(Builder $query, bool $includeMonevNotes = false): array
    {
        $columns = [
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
        ];

        if ($includeMonevNotes) {
            $columns[] = 'catatan_kebutuhan_hasil_monev';
        }

        $rows = $query
            ->with(['kawasan:id,nama_kawasan', 'desa:id,nama_desa'])
            ->select($columns)
            ->orderBy('nama_kelompok')
            ->limit(31)
            ->get();

        return [
            'rows' => $rows
                ->take(30)
                ->map(function (Kelompok $kelompok) use ($includeMonevNotes): array {
                    $row = [
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
                        'jumlah_bantuan_rupiah' => (float) $kelompok->jumlah_bantuan,
                    ];

                    if ($includeMonevNotes) {
                        $row['catatan_monev'] = $this->sanitizer->redactContactDetails($kelompok->catatan_kebutuhan_hasil_monev);
                    }

                    return $row;
                })
                ->all(),
            'truncated' => $rows->count() > 30,
        ];
    }
}
