<?php

namespace App\Console\Commands;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

#[Signature('kelompok:import-excel {path : Path to the source XLSX file} {--dry-run : Preview without changing the database}')]
#[Description('Import supported group records from the XLSX workbook')]
class ImportGroupsFromExcel extends Command
{
    /**
     * Known workbook abbreviations are accepted only when the desa_kawasan
     * relation already confirms the corresponding database area.
     *
     * @var array<string, string>
     */
    private const KAWASAN_ALIASES = [
        'cagntinombala' => 'cagunungtinombala',
        'smbangkiriang' => 'smbakiriang',
        'smpinjantjmatop' => 'smpinjan',
        'smtjsantigi' => 'smtanjungsantigi',
        'twapulautokobae' => 'twatokobae',
    ];

    /**
     * @var array<int, string>
     */
    private const EXCEL_COLUMNS = [
        2 => 'tahun',
        3 => 'kawasan',
        4 => 'desa',
        5 => 'kecamatan',
        6 => 'kabupaten',
        7 => 'nama_kelompok',
        8 => 'nama_ketua',
        9 => 'no_hp',
        10 => 'jenis_usaha',
        11 => 'sk_kelompok',
        12 => 'jumlah_anggota',
        13 => 'jumlah_bantuan',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("File Excel tidak ditemukan atau tidak dapat dibaca: {$path}");

            return self::FAILURE;
        }

        try {
            $records = $this->readWorkbook($path);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($records === []) {
            $this->error('Tidak ada baris kelompok yang dapat dibaca dari workbook.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $report = DB::transaction(function () use ($records, $dryRun): array {
            return $this->importRecords($records, $dryRun);
        }, attempts: 3);

        $this->info($dryRun ? 'Simulasi import selesai; database tidak diubah.' : 'Import selesai.');
        $this->line('Baris sumber: '.count($records));
        $this->line('Berhasil diperbarui: '.$report['updated']);
        $this->line('Berhasil ditambahkan: '.$report['added']);
        $this->line('Gagal dicocokkan: '.count($report['failures']));

        if ($report['failures'] !== []) {
            $this->table(
                ['Baris Excel', 'Nama kelompok', 'Alasan'],
                $report['failures']
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, int|float|string|null>>  $records
     * @return array{updated: int, added: int, failures: array<int, array{int, string, string}>}
     */
    private function importRecords(array $records, bool $dryRun): array
    {
        $kawasans = Kawasan::query()->get(['id', 'nama_kawasan']);
        $desas = Desa::query()
            ->with('kawasans:id,nama_kawasan')
            ->get(['id', 'nama_desa', 'kecamatan', 'kabupaten']);
        $kelompoks = Kelompok::query()
            ->get(['id', 'kawasan_id', 'tahun_pembinaan', 'nama_kelompok']);

        $groupsByKey = [];

        foreach ($kelompoks as $kelompok) {
            $key = $this->groupKey(
                (int) $kelompok->kawasan_id,
                (int) $kelompok->tahun_pembinaan,
                (string) $kelompok->nama_kelompok
            );
            $groupsByKey[$key][] = $kelompok;
        }

        $updated = 0;
        $added = 0;
        $failures = [];

        foreach ($records as $record) {
            $rowNumber = (int) ($record['row'] ?? 0);
            $groupName = (string) ($record['nama_kelompok'] ?? '');
            $year = (int) ($record['tahun'] ?? 0);
            $sourceArea = (string) ($record['kawasan'] ?? '');

            if (
                $year < 1
                || $groupName === ''
                || $sourceArea === ''
                || (string) ($record['desa'] ?? '') === ''
                || (string) ($record['kecamatan'] ?? '') === ''
                || (string) ($record['kabupaten'] ?? '') === ''
            ) {
                $failures[] = [$rowNumber, $groupName, 'Data tahun atau lokasi tidak lengkap.'];

                continue;
            }

            [$kawasan, $isAreaAlias] = $this->resolveKawasan($kawasans, $sourceArea);

            if ($kawasan === null) {
                if ($dryRun && $this->normalize($sourceArea) === 'twapulautokobae') {
                    $added++;

                    continue;
                }

                $kawasan = $this->createMissingAliasKawasan($sourceArea);

                if ($kawasan === null) {
                    $failures[] = [$rowNumber, $groupName, "Kawasan tidak ditemukan: {$sourceArea}."];

                    continue;
                }

                $kawasans->push($kawasan);
                $isAreaAlias = true;
            }

            if ($kawasan === null) {
                $failures[] = [$rowNumber, $groupName, "Kawasan tidak ditemukan: {$sourceArea}."];

                continue;
            }

            $desa = $this->resolveDesa($desas, $record, $kawasan);

            if ($desa === null) {
                if ($dryRun) {
                    $added++;

                    continue;
                }

                $desa = $this->createDesaFromRecord($record, $kawasan);

                if ($desa === null) {
                    $failures[] = [
                        $rowNumber,
                        $groupName,
                        'Lokasi tidak cocok di referensi: '
                            .(string) $record['desa'].', '
                            .(string) $record['kecamatan'].', '
                            .(string) $record['kabupaten'].' ('.$sourceArea.').',
                    ];

                    continue;
                }

                $desas->push($desa);
            }

            if ($isAreaAlias && ! $this->desaHasKawasan($desa, $kawasan)) {
                $failures[] = [
                    $rowNumber,
                    $groupName,
                    "Relasi desa_kawasan tidak mengonfirmasi alias kawasan {$sourceArea}.",
                ];

                continue;
            }

            $key = $this->groupKey((int) $kawasan->id, $year, $groupName);
            $existing = $groupsByKey[$key] ?? [];

            if (count($existing) > 1) {
                $failures[] = [
                    $rowNumber,
                    $groupName,
                    'Ada lebih dari satu kelompok dengan nama, kawasan, dan tahun yang sama.',
                ];

                continue;
            }

            $payload = $this->groupPayload($record, $desa);

            if ($existing !== []) {
                if (! $dryRun) {
                    $existing[0]->forceFill($payload)->save();

                    if (! $this->desaHasKawasan($desa, $kawasan)) {
                        $desa->kawasans()->syncWithoutDetaching([$kawasan->id]);
                    }
                }

                $updated++;

                continue;
            }

            if (! $dryRun) {
                $kelompok = Kelompok::query()->create([
                    'kawasan_id' => $kawasan->id,
                    ...$payload,
                    'tahun_pembinaan' => $year,
                    'nama_kelompok' => $groupName,
                    'jumlah_anggota' => $this->parseMemberCount(
                        (string) ($record['jumlah_anggota'] ?? '')
                    ) ?? 0,
                    'jumlah_bantuan' => $this->parseAmount(
                        (string) ($record['jumlah_bantuan'] ?? '')
                    ) ?? 0,
                ]);

                if (! $this->desaHasKawasan($desa, $kawasan)) {
                    $desa->kawasans()->syncWithoutDetaching([$kawasan->id]);
                }

                $groupsByKey[$key][] = $kelompok;
            }

            $added++;
        }

        return [
            'updated' => $updated,
            'added' => $added,
            'failures' => $failures,
        ];
    }

    private function createMissingAliasKawasan(string $sourceName): ?Kawasan
    {
        if ($this->normalize($sourceName) !== 'twapulautokobae') {
            return null;
        }

        return Kawasan::query()->firstOrCreate(
            ['nama_kawasan' => 'TWA Tokobae'],
            [
                'fungsi_kawasan' => 'TWA',
                'jumlah_desa_sekitar' => 0,
                'jumlah_desa_difasilitasi' => 0,
            ]
        );
    }

    /**
     * @param  Collection<int, Kawasan>  $kawasans
     * @return array{0: Kawasan|null, 1: bool}
     */
    private function resolveKawasan(Collection $kawasans, string $sourceName): array
    {
        $sourceKey = $this->normalize($sourceName);
        $targetKey = self::KAWASAN_ALIASES[$sourceKey] ?? $sourceKey;
        $matches = $kawasans->filter(
            fn (Kawasan $kawasan): bool => $this->normalize($kawasan->nama_kawasan) === $targetKey
        );

        if ($matches->count() !== 1) {
            return [null, $targetKey !== $sourceKey];
        }

        return [$matches->first(), $targetKey !== $sourceKey];
    }

    /**
     * @param  EloquentCollection<int, Desa>  $desas
     * @param  array<string, int|float|string|null>  $record
     */
    private function resolveDesa(EloquentCollection $desas, array $record, Kawasan $kawasan): ?Desa
    {
        $nameMatches = $desas->filter(
            fn (Desa $desa): bool => $this->normalize($desa->nama_desa) === $this->normalize(
                (string) $record['desa']
            )
        );

        if ($nameMatches->isEmpty()) {
            return null;
        }

        $districtKey = $this->normalize((string) $record['kecamatan']);
        $countyKey = $this->normalize((string) $record['kabupaten']);
        $exactLocation = $nameMatches->filter(
            fn (Desa $desa): bool => $this->normalize((string) $desa->kecamatan) === $districtKey
                && $this->normalize((string) $desa->kabupaten) === $countyKey
        );

        if ($exactLocation->count() === 1) {
            return $exactLocation->first();
        }

        $sameCounty = $nameMatches->filter(
            fn (Desa $desa): bool => $this->normalize((string) $desa->kabupaten) === $countyKey
        );

        if ($sameCounty->count() === 1 && $this->desaHasKawasan($sameCounty->first(), $kawasan)) {
            return $sameCounty->first();
        }

        $sameDistrict = $sameCounty->filter(
            fn (Desa $desa): bool => $this->normalize((string) $desa->kecamatan) === $districtKey
        );

        if ($sameDistrict->count() === 1) {
            return $sameDistrict->first();
        }

        $sameArea = $nameMatches->filter(
            fn (Desa $desa): bool => $this->desaHasKawasan($desa, $kawasan)
                && $this->normalize((string) $desa->kabupaten) === $countyKey
        );

        return $sameArea->count() === 1 ? $sameArea->first() : null;
    }

    /**
     * @param  array<string, int|float|string|null>  $record
     */
    private function createDesaFromRecord(array $record, Kawasan $kawasan): ?Desa
    {
        $desaName = trim((string) ($record['desa'] ?? ''));
        $district = trim((string) ($record['kecamatan'] ?? ''));
        $county = trim((string) ($record['kabupaten'] ?? ''));

        if ($desaName === '' || $district === '' || $county === '') {
            return null;
        }

        $desa = Desa::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => $desaName,
            'kecamatan' => $district,
            'kabupaten' => $county,
        ]);
        $desa->kawasans()->syncWithoutDetaching([$kawasan->id]);

        return $desa;
    }

    private function desaHasKawasan(Desa $desa, Kawasan $kawasan): bool
    {
        return $desa->kawasans->contains('id', $kawasan->id);
    }

    /**
     * @param  array<string, int|float|string|null>  $record
     * @return array<string, int|string>
     */
    private function groupPayload(array $record, Desa $desa): array
    {
        $payload = [
            'desa_id' => (int) $desa->id,
            'kecamatan' => (string) $record['kecamatan'],
            'kabupaten' => (string) $record['kabupaten'],
        ];

        foreach (['nama_ketua', 'no_hp', 'jenis_usaha', 'sk_kelompok'] as $field) {
            $value = trim((string) ($record[$field] ?? ''));

            if ($value !== '') {
                $payload[$field] = $value;
            }
        }

        $memberCount = $this->parseMemberCount((string) ($record['jumlah_anggota'] ?? ''));

        if ($memberCount !== null) {
            $payload['jumlah_anggota'] = $memberCount;
        }

        return $payload;
    }

    private function groupKey(int $kawasanId, int $year, string $name): string
    {
        return $kawasanId.'|'.$year.'|'.$this->normalize($name);
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::ascii(Str::lower(trim($value)))) ?? '';
    }

    private function parseMemberCount(string $value): ?int
    {
        return preg_match('/^\s*(\d+)/', $value, $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    private function parseAmount(string $value): ?float
    {
        $value = trim($value);

        return $value !== '' && is_numeric($value) ? (float) $value : null;
    }

    /**
     * @return array<int, array<string, int|float|string|null>>
     */
    private function readWorkbook(string $path): array
    {
        $archive = new ZipArchive;

        if ($archive->open($path) !== true) {
            throw new RuntimeException('Workbook bukan file XLSX yang dapat dibaca.');
        }

        try {
            $workbook = $this->loadXml($archive->getFromName('xl/workbook.xml'), 'xl/workbook.xml');
            $relationships = $this->loadXml(
                $archive->getFromName('xl/_rels/workbook.xml.rels'),
                'xl/_rels/workbook.xml.rels'
            );
            $workbook->registerXPathNamespace(
                'x',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );
            $sheets = $workbook->xpath('//x:sheets/x:sheet');

            if ($sheets === false || $sheets === []) {
                throw new RuntimeException('Workbook tidak memiliki worksheet.');
            }

            $relationshipAttributes = $sheets[0]->attributes(
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
            );
            $relationshipId = (string) ($relationshipAttributes['id'] ?? '');
            $relationships->registerXPathNamespace(
                'x',
                'http://schemas.openxmlformats.org/package/2006/relationships'
            );
            $relationshipNodes = $relationships->xpath('//x:Relationship');
            $sheetTarget = null;

            foreach ($relationshipNodes ?: [] as $relationship) {
                if ((string) $relationship['Id'] === $relationshipId) {
                    $sheetTarget = (string) $relationship['Target'];

                    break;
                }
            }

            if ($sheetTarget === null || str_contains($sheetTarget, '..')) {
                throw new RuntimeException('Worksheet pertama tidak dapat ditemukan.');
            }

            $worksheetPath = str_starts_with($sheetTarget, '/')
                ? ltrim($sheetTarget, '/')
                : 'xl/'.ltrim($sheetTarget, '/');
            $worksheet = $this->loadXml(
                $archive->getFromName($worksheetPath),
                $worksheetPath
            );
            $worksheet->registerXPathNamespace(
                'x',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );

            $sharedStrings = [];
            $sharedStringsXml = $archive->getFromName('xl/sharedStrings.xml');

            if ($sharedStringsXml !== false) {
                $sharedStringRoot = $this->loadXml($sharedStringsXml, 'xl/sharedStrings.xml');
                $sharedStringRoot->registerXPathNamespace(
                    'x',
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                );

                foreach ($sharedStringRoot->xpath('//x:si') ?: [] as $item) {
                    $item->registerXPathNamespace(
                        'x',
                        'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                    );
                    $text = '';

                    foreach ($item->xpath('.//x:t') ?: [] as $textNode) {
                        $text .= (string) $textNode;
                    }

                    $sharedStrings[] = $text;
                }
            }

            $rows = $worksheet->xpath('//x:sheetData/x:row');
            $records = [];
            $currentYear = null;

            foreach ($rows ?: [] as $row) {
                $row->registerXPathNamespace(
                    'x',
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                );
                $cells = [];

                foreach ($row->xpath('x:c') ?: [] as $cell) {
                    $cell->registerXPathNamespace(
                        'x',
                        'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                    );
                    $reference = (string) $cell['r'];
                    $column = preg_replace('/\d+/', '', $reference) ?? '';
                    $valueNodes = $cell->xpath('x:v');
                    $value = $valueNodes === false || $valueNodes === []
                        ? ''
                        : (string) $valueNodes[0];
                    $cellType = (string) ($cell['t'] ?? '');

                    if ($cellType === 's' && $value !== '') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($cellType === 'inlineStr') {
                        $inlineText = '';

                        foreach ($cell->xpath('x:is//x:t') ?: [] as $textNode) {
                            $inlineText .= (string) $textNode;
                        }

                        $value = $inlineText;
                    }

                    $cells[$column] = trim($value);
                }

                if (isset($cells['B']) && preg_match('/^\d{4}$/', $cells['B']) === 1) {
                    $currentYear = (int) $cells['B'];
                }

                $groupName = (string) ($cells['G'] ?? '');

                if ($groupName === '' || Str::lower($groupName) === 'nama kelompok') {
                    continue;
                }

                $record = [
                    'row' => (int) $row['r'],
                    'tahun' => $currentYear,
                ];

                foreach (self::EXCEL_COLUMNS as $column => $field) {
                    if ($field !== 'tahun') {
                        $record[$field] = $cells[$this->columnName($column)] ?? '';
                    }
                }

                $records[] = $record;
            }

            return $records;
        } finally {
            $archive->close();
        }
    }

    private function loadXml(string|false $contents, string $entry): SimpleXMLElement
    {
        if ($contents === false) {
            throw new RuntimeException("Bagian {$entry} tidak ditemukan di workbook.");
        }

        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET);

        if ($xml === false) {
            throw new RuntimeException("Bagian {$entry} bukan XML yang valid.");
        }

        return $xml;
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr($index % 26 + 65).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
