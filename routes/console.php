<?php

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

if (! function_exists('normalizeImportHeader')) {
    function normalizeImportHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-', '/'], '_', $header);
        $header = preg_replace('/[^a-z0-9_]/', '', $header);

        return trim($header, '_');
    }
}

if (! function_exists('parseNumber')) {
    function parseNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $clean = preg_replace('/[^0-9,.-]/', '', (string) $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}

if (! function_exists('simpleKMeansCluster')) {
    function simpleKMeansCluster(array $records, int $clusterCount = 3): array
    {
        if ($clusterCount < 1 || $records === []) {
            return $records;
        }

        $maxNte = max(array_map(fn ($record) => (float) ($record['total_nte'] ?? 0), $records));
        $maxDesa = max(array_map(fn ($record) => (int) ($record['jumlah_desa_sekitar'] ?? 0), $records));

        foreach ($records as $index => $record) {
            $records[$index]['feature_nte'] = $maxNte > 0 ? ((float) ($record['total_nte'] ?? 0)) / $maxNte : 0;
            $records[$index]['feature_desa'] = $maxDesa > 0 ? ((int) ($record['jumlah_desa_sekitar'] ?? 0)) / $maxDesa : 0;
        }

        $centroids = [];
        $sorted = collect($records)->sortByDesc('total_nte')->values()->all();
        $step = max(1, intdiv(count($sorted), $clusterCount));

        for ($i = 0; $i < $clusterCount; $i++) {
            $pivot = $sorted[min(count($sorted) - 1, $i * $step)]['feature_nte'] ?? 0;
            $centroids[] = [
                'x' => $pivot,
                'y' => (($i + 1) / max(1, $clusterCount)),
            ];
        }

        for ($iteration = 0; $iteration < 15; $iteration++) {
            $assignments = array_fill(0, count($records), 0);

            foreach ($records as $index => $record) {
                $best = 0;
                $bestDistance = PHP_FLOAT_MAX;

                foreach ($centroids as $centroidIndex => $centroid) {
                    $distance = sqrt(
                        pow(($record['feature_nte'] ?? 0) - $centroid['x'], 2) +
                        pow(($record['feature_desa'] ?? 0) - $centroid['y'], 2)
                    );

                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $best = $centroidIndex;
                    }
                }

                $assignments[$index] = $best;
            }

            $newCentroids = [];
            for ($clusterIndex = 0; $clusterIndex < $clusterCount; $clusterIndex++) {
                $items = [];
                foreach ($records as $index => $record) {
                    if (($assignments[$index] ?? 0) === $clusterIndex) {
                        $items[] = $record;
                    }
                }

                if ($items === []) {
                    $newCentroids[$clusterIndex] = $centroids[$clusterIndex] ?? ['x' => 0, 'y' => 0];
                    continue;
                }

                $sumNte = array_sum(array_map(fn ($item) => (float) ($item['feature_nte'] ?? 0), $items));
                $sumDesa = array_sum(array_map(fn ($item) => (float) ($item['feature_desa'] ?? 0), $items));

                $newCentroids[$clusterIndex] = [
                    'x' => $sumNte / count($items),
                    'y' => $sumDesa / count($items),
                ];
            }

            $centroids = $newCentroids;
        }

        $clusterLabels = ['Rendah', 'Sedang', 'Tinggi'];
        foreach ($records as $index => $record) {
            $best = 0;
            $bestDistance = PHP_FLOAT_MAX;

            foreach ($centroids as $centroidIndex => $centroid) {
                $distance = sqrt(
                    pow(($record['feature_nte'] ?? 0) - $centroid['x'], 2) +
                    pow(($record['feature_desa'] ?? 0) - $centroid['y'], 2)
                );

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $best = $centroidIndex;
                }
            }

            $records[$index]['cluster'] = $clusterLabels[$best] ?? 'Rendah';
        }

        return $records;
    }
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('data:import {type} {path} {--truncate} {--dry-run}', function ($type, $path) {
    $truncate = $this->option('truncate');
    $dryRun = $this->option('dry-run');

    $allowedTypes = ['kawasan', 'nte', 'kelompok'];

    if (! in_array($type, $allowedTypes, true)) {
        $this->error('Tipe tidak valid. Pilih: '.implode(', ', $allowedTypes));

        return 1;
    }

    $resolvedPath = $path;
    if (! file_exists($resolvedPath)) {
        $fallback = storage_path('app/templates/'.$path);
        if (file_exists($fallback)) {
            $resolvedPath = $fallback;
        }
    }

    if (! file_exists($resolvedPath)) {
        $this->error('File CSV tidak ditemukan: '.$path);

        return 1;
    }

    $handle = fopen($resolvedPath, 'r');
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    if (count($rows) < 2) {
        $this->error('File CSV tidak memiliki data baris.');

        return 1;
    }

    $header = array_map(fn ($column) => normalizeImportHeader((string) $column), $rows[0]);
    $dataRows = array_slice($rows, 1);
    $imported = 0;

    if ($truncate && ! $dryRun) {
        if ($type === 'kawasan') {
            Kawasan::query()->delete();
        }

        if ($type === 'nte') {
            Nte::query()->delete();
        }

        if ($type === 'kelompok') {
            Kelompok::query()->delete();
        }
    }

    foreach ($dataRows as $rowIndex => $row) {
        $record = [];
        foreach ($header as $index => $key) {
            $record[$key] = $row[$index] ?? '';
        }

        if (empty(array_filter($record, fn ($value) => trim((string) $value) !== ''))) {
            continue;
        }

        if ($type === 'kawasan') {
            $namaKawasan = trim((string) ($record['nama_kawasan'] ?? $record['kawasan'] ?? ''));
            if ($namaKawasan === '') {
                continue;
            }

            $payload = [
                'nama_kawasan' => $namaKawasan,
                'fungsi_kawasan' => trim((string) ($record['fungsi_kawasan'] ?? $record['fungsi'] ?? '')) ?: null,
                'jumlah_desa_sekitar' => (int) round(parseNumber($record['jumlah_desa_sekitar'] ?? $record['desa_sekitar'] ?? 0)),
                'jumlah_desa_difasilitasi' => (int) round(parseNumber($record['jumlah_desa_difasilitasi'] ?? $record['desa_difasilitasi'] ?? 0)),
            ];

            if (! $dryRun) {
                Kawasan::firstOrCreate(['nama_kawasan' => $namaKawasan], $payload);
            }

            $imported++;
        }

        if ($type === 'nte') {
            $namaKawasan = trim((string) ($record['nama_kawasan'] ?? $record['kawasan'] ?? ''));
            $tahun = (int) round(parseNumber($record['tahun'] ?? 2025));

            if ($namaKawasan === '') {
                continue;
            }

            $kawasan = Kawasan::firstOrCreate(['nama_kawasan' => $namaKawasan]);

            $payload = [
                'kawasan_id' => $kawasan->id,
                'tahun' => $tahun,
                'nte_langsung' => (float) parseNumber($record['nte_langsung'] ?? $record['nte_langsung'] ?? 0),
                'nte_tidak_langsung' => (float) parseNumber($record['nte_tidak_langsung'] ?? $record['nte_tidak_langsung'] ?? 0),
                'total_nte' => (float) parseNumber($record['total_nte'] ?? $record['total'] ?? 0),
            ];

            if (! $dryRun) {
                Nte::updateOrCreate([
                    'kawasan_id' => $kawasan->id,
                    'tahun' => $tahun,
                ], $payload);
            }

            $imported++;
        }

        if ($type === 'kelompok') {
            $namaKawasan = trim((string) ($record['nama_kawasan'] ?? $record['kawasan'] ?? ''));
            $namaDesa = trim((string) ($record['nama_desa'] ?? $record['desa'] ?? ''));
            $kecamatan = trim((string) ($record['kecamatan'] ?? ''));
            $kabupaten = trim((string) ($record['kabupaten'] ?? ''));
            $namaKelompok = trim((string) ($record['nama_kelompok'] ?? $record['kelompok'] ?? ''));

            if ($namaKawasan === '' || $namaKelompok === '') {
                continue;
            }

            $kawasan = Kawasan::firstOrCreate(['nama_kawasan' => $namaKawasan]);
            $desa = null;

            if ($namaDesa !== '') {
                $desa = Desa::firstOrCreate([
                    'nama_desa' => $namaDesa,
                    'kawasan_id' => $kawasan->id,
                ]);
            }

            $payload = [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa?->id,
                'kecamatan' => $kecamatan !== '' ? $kecamatan : null,
                'kabupaten' => $kabupaten !== '' ? $kabupaten : null,
                'tahun_pembinaan' => (int) round(parseNumber($record['tahun_pembinaan'] ?? $record['tahun'] ?? 0)),
                'nama_kelompok' => $namaKelompok,
                'nama_ketua' => trim((string) ($record['nama_ketua'] ?? $record['ketua'] ?? '')) ?: null,
                'no_hp' => trim((string) ($record['no_hp'] ?? $record['hp'] ?? '')) ?: null,
                'jenis_usaha' => trim((string) ($record['jenis_usaha'] ?? $record['usaha'] ?? '')) ?: null,
                'produk_unggulan' => trim((string) ($record['produk_unggulan'] ?? $record['produk'] ?? '')) ?: null,
                'sk_kelompok' => trim((string) ($record['sk_kelompok'] ?? $record['sk'] ?? '')) ?: null,
                'jumlah_anggota' => (int) round(parseNumber($record['jumlah_anggota'] ?? $record['anggota'] ?? 0)),
                'jumlah_bantuan' => (float) parseNumber($record['jumlah_bantuan'] ?? $record['bantuan'] ?? 0),
            ];

            if (! $dryRun) {
                Kelompok::updateOrCreate([
                    'kawasan_id' => $kawasan->id,
                    'nama_kelompok' => $namaKelompok,
                    'tahun_pembinaan' => $payload['tahun_pembinaan'],
                ], $payload);
            }

            $imported++;
        }
    }

    $status = $dryRun ? 'akan diimport' : 'diimport';
    $this->info("Data {$type} {$status}: {$imported} baris berhasil diproses.");

    return 0;
})->purpose('Import data kawasan, NTE, atau kelompok dari file CSV');

Artisan::command('data:mine {--csv}', function () {
    $totalNte = Nte::sum('total_nte');
    $rows = Kawasan::with(['nte', 'desas'])->get()->map(function ($kawasan) use ($totalNte) {
        $total = (float) ($kawasan->nte?->total_nte ?? 0);

        return [
            'ranking' => 0,
            'nama_kawasan' => $kawasan->nama_kawasan,
            'fungsi_kawasan' => $kawasan->fungsi_kawasan,
            'jumlah_desa_sekitar' => (int) ($kawasan->jumlah_desa_sekitar ?? $kawasan->desas()->count()),
            'total_nte' => $total,
            'kontribusi' => $totalNte > 0 ? ($total / $totalNte) * 100 : 0,
        ];
    })->filter(fn ($row) => (float) ($row['total_nte'] ?? 0) > 0)->values()->all();

    $rows = simpleKMeansCluster($rows, 3);
    $rows = collect($rows)->sortByDesc('total_nte')->values()->map(function ($row, $index) {
        $row['ranking'] = $index + 1;

        return $row;
    });

    $this->table(
        ['Ranking', 'Kawasan', 'Cluster', 'Total NTE', 'Kontribusi'],
        $rows->map(fn ($row) => [
            $row['ranking'],
            $row['nama_kawasan'],
            $row['cluster'],
            'Rp '.number_format($row['total_nte'], 0, ',', '.'),
            number_format($row['kontribusi'], 2, ',', '.').'%',
        ])->all()
    );

    if ($this->option('csv')) {
        $path = storage_path('app/templates/nte_2025_summary.csv');
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $handle = fopen($path, 'w');
        fputcsv($handle, ['ranking', 'nama_kawasan', 'fungsi_kawasan', 'jumlah_desa_sekitar', 'cluster', 'total_nte', 'kontribusi_persen']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['ranking'],
                $row['nama_kawasan'],
                $row['fungsi_kawasan'],
                $row['jumlah_desa_sekitar'],
                $row['cluster'],
                (float) $row['total_nte'],
                number_format($row['kontribusi'], 2, '.', ''),
            ]);
        }

        fclose($handle);

        $this->info('Ringkasan data mining dan clustering kawasan disimpan di: '.$path);
    }

    $this->info('Data mining NTE dan clustering kawasan berhasil dihitung dari '.count($rows).' kawasan.');
})->purpose('Menganalisis dan mengelompokkan kontribusi NTE per kawasan untuk proyek data mining');
