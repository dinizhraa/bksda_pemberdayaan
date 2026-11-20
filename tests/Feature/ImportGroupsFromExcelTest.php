<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use ZipArchive;

class ImportGroupsFromExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_updates_and_adds_groups_without_overwriting_existing_assistance_or_nte(): void
    {
        $kawasan = $this->createKawasan('CA Morowali');
        $desa = $this->createDesa('Uewajo', 'Bungku Utara', 'Morowali Utara');
        $legacyGroup = Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'tahun_pembinaan' => 2024,
            'nama_kelompok' => 'Kelompok Lama',
            'jumlah_anggota' => 3,
            'jumlah_bantuan' => 9000000,
        ]);
        $nte = Nte::query()->create([
            'kawasan_id' => $kawasan->id,
            'tahun' => 2025,
            'nte_langsung' => 100,
            'nte_tidak_langsung' => 200,
            'total_nte' => 300,
        ]);
        $path = $this->createWorkbook([
            4 => $this->groupRow('2024', 'CA Morowali', 'Uewajo', 'Bungku Utara', 'Morowali Utara', 'Kelompok Lama', 'Ketua Lama', '087700', 'Budidaya ikan', 'SK-OLD', '8', '23000000'),
            5 => $this->groupRow('', 'CA Morowali', 'Uewajo', 'Bungku Utara', 'Morowali Utara', 'Kelompok Baru', 'Ketua Baru', '081200', 'Olahan ikan', 'SK-NEW', '12 (P)', '23200000'),
            6 => $this->groupRow('', 'CA Morowali', 'Desa Baru', 'Bungku Utara', 'Morowali Utara', 'Kelompok Desa Baru', '', '', '', '', '6', '12000000'),
        ]);

        try {
            $exitCode = Artisan::call('kelompok:import-excel', ['path' => $path]);
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString('Berhasil diperbarui: 1', $output);
            $this->assertStringContainsString('Berhasil ditambahkan: 2', $output);
            $this->assertStringContainsString('Gagal dicocokkan: 0', $output);

            $this->assertDatabaseHas('kelompoks', [
                'id' => $legacyGroup->id,
                'desa_id' => $desa->id,
                'kecamatan' => 'Bungku Utara',
                'kabupaten' => 'Morowali Utara',
                'nama_ketua' => 'Ketua Lama',
                'jumlah_anggota' => 8,
                'jumlah_bantuan' => 9000000,
            ]);
            $this->assertDatabaseHas('kelompoks', [
                'nama_kelompok' => 'Kelompok Baru',
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'jumlah_anggota' => 12,
                'jumlah_bantuan' => 23200000,
            ]);
            $this->assertDatabaseHas('desas', [
                'nama_desa' => 'Desa Baru',
                'kecamatan' => 'Bungku Utara',
                'kabupaten' => 'Morowali Utara',
            ]);
            $this->assertDatabaseHas('kelompoks', [
                'nama_kelompok' => 'Kelompok Desa Baru',
                'kawasan_id' => $kawasan->id,
                'jumlah_anggota' => 6,
                'jumlah_bantuan' => 12000000,
            ]);
            $this->assertDatabaseHas('desa_kawasan', [
                'desa_id' => $desa->id,
                'kawasan_id' => $kawasan->id,
            ]);
            $this->assertModelExists($nte->fresh());

            Artisan::call('kelompok:import-excel', ['path' => $path]);

            $this->assertDatabaseCount('kelompoks', 3);
            $this->assertDatabaseHas('kelompoks', [
                'id' => $legacyGroup->id,
                'jumlah_bantuan' => 9000000,
            ]);
            $this->assertModelExists($nte->fresh());
        } finally {
            unlink($path);
        }
    }

    public function test_area_alias_is_not_used_without_a_matching_desa_kawasan_relation(): void
    {
        $kawasan = $this->createKawasan('SM Bakiriang');
        $this->createDesa('Moilong', 'Moilong', 'Banggai');
        $path = $this->createWorkbook([
            4 => $this->groupRow('2024', 'SM Bangkiriang', 'Moilong', 'Moilong', 'Banggai', 'Kelompok Alias', '', '', '', '', '10', '5000000'),
        ]);

        try {
            $exitCode = Artisan::call('kelompok:import-excel', ['path' => $path]);
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString('Gagal dicocokkan: 1', $output);
            $this->assertDatabaseMissing('kelompoks', [
                'nama_kelompok' => 'Kelompok Alias',
                'kawasan_id' => $kawasan->id,
            ]);
        } finally {
            unlink($path);
        }
    }

    public function test_invalid_xlsx_is_rejected_without_database_changes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kelompok_');
        file_put_contents($path, 'not an XLSX workbook');

        try {
            $exitCode = Artisan::call('kelompok:import-excel', ['path' => $path]);

            $this->assertSame(1, $exitCode);
            $this->assertDatabaseCount('kelompoks', 0);
        } finally {
            unlink($path);
        }
    }

    private function createKawasan(string $name): Kawasan
    {
        return Kawasan::query()->create([
            'nama_kawasan' => $name,
            'fungsi_kawasan' => 'CA',
            'jumlah_desa_sekitar' => 0,
            'jumlah_desa_difasilitasi' => 0,
        ]);
    }

    private function createDesa(string $name, string $district, string $county): Desa
    {
        return Desa::query()->create([
            'nama_desa' => $name,
            'kecamatan' => $district,
            'kabupaten' => $county,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function groupRow(
        string $year,
        string $area,
        string $village,
        string $district,
        string $county,
        string $group,
        string $leader,
        string $phone,
        string $business,
        string $decree,
        string $members,
        string $assistance
    ): array {
        return [
            'B' => $year,
            'C' => $area,
            'D' => $village,
            'E' => $district,
            'F' => $county,
            'G' => $group,
            'H' => $leader,
            'I' => $phone,
            'J' => $business,
            'K' => $decree,
            'L' => $members,
            'M' => $assistance,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $dataRows
     */
    private function createWorkbook(array $dataRows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kelompok_');
        $zip = new ZipArchive;
        $sharedStrings = [];
        $sharedStringIndexes = [];
        $xmlRows = [];
        $rows = [
            3 => ['B' => 'Tahun', 'G' => 'Nama Kelompok'],
            ...$dataRows,
        ];

        foreach ($rows as $rowNumber => $cells) {
            $xmlCells = [];

            foreach ($cells as $column => $value) {
                if ($value === '') {
                    continue;
                }

                $reference = $column.$rowNumber;

                if (in_array($column, ['B', 'L', 'M'], true) && is_numeric($value)) {
                    $xmlCells[] = "<c r=\"{$reference}\"><v>{$value}</v></c>";

                    continue;
                }

                if (! array_key_exists($value, $sharedStringIndexes)) {
                    $sharedStringIndexes[$value] = count($sharedStrings);
                    $sharedStrings[] = $value;
                }

                $xmlCells[] = "<c r=\"{$reference}\" t=\"s\"><v>{$sharedStringIndexes[$value]}</v></c>";
            }

            $xmlRows[] = '<row r="'.$rowNumber.'">'.implode('', $xmlCells).'</row>';
        }

        $this->assertSame(true, $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Groups" sheetId="1" r:id="rId1"/></sheets></workbook>'
        );
        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>'
        );
        $zip->addFromString(
            'xl/worksheets/sheet1.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<sheetData>'.implode('', $xmlRows).'</sheetData></worksheet>'
        );
        $stringItems = array_map(
            fn (string $value): string => '<si><t>'.htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            ).'</t></si>',
            $sharedStrings
        );
        $zip->addFromString(
            'xl/sharedStrings.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
                .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .implode('', $stringItems).'</sst>'
        );
        $zip->close();

        return $path;
    }
}
