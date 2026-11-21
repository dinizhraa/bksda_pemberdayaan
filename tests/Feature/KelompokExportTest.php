<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class KelompokExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_a_filtered_csv_with_all_group_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create(['kawasan_id' => $kawasan->id, 'nama_desa' => 'Desa Export']);
        $kelompok = $this->createGroup($kawasan, $desa, 'Kelompok Export Target', [
            'no_hp' => '08123456789',
            'jumlah_bantuan' => '12500000.50',
            'latitude' => '-0.9012000',
            'longitude' => '119.8701000',
            'catatan_kebutuhan_hasil_monev' => "Kebutuhan air, jalan, dan pendampingan.\nTindak lanjut lapangan.",
        ]);
        $this->createGroup($kawasan, $desa, 'Kelompok Export Target Kedua');
        $this->createGroup($kawasan, $desa, 'Kelompok Lain');

        $response = $this->actingAs($admin)
            ->get(route('kelompok.export', ['format' => 'csv', 'search' => 'Export Target']));

        $response->assertDownload('kelompok-binaan.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($csv, 3));
        rewind($stream);
        $headers = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        $secondRow = fgetcsv($stream, 0, ',', '"', '');
        $empty = fgetcsv($stream, 0, ',', '"', '');
        fclose($stream);

        $this->assertIsArray($headers);
        $this->assertIsArray($row);
        $this->assertIsArray($secondRow);
        $this->assertFalse($empty);
        $this->assertCount(24, $headers);
        $this->assertSame(['No', 'Nama Kawasan', 'Nama Desa'], array_slice($headers, 0, 3));
        $this->assertNotContains('ID Kelompok', $headers);
        $this->assertNotContains('ID Kawasan', $headers);
        $this->assertNotContains('ID Desa', $headers);
        $exported = array_combine($headers, $row);
        $this->assertIsArray($exported);
        $this->assertSame('1', $exported['No']);
        $this->assertSame('Aktif', $exported['Status Kelompok']);
        $this->assertSame('SM Bakiriang', $exported['Nama Kawasan']);
        $this->assertSame('Desa Export', $exported['Nama Desa']);
        $this->assertSame('08123456789', $exported['Nomor HP / Kontak']);
        $this->assertSame('12500000.50', $exported['Jumlah Bantuan (Rp)']);
        $this->assertSame('-0.9012000', $exported['Latitude']);
        $this->assertSame("Kebutuhan air, jalan, dan pendampingan.\nTindak lanjut lapangan.", $exported['Catatan Kebutuhan / Hasil Monev']);
        $secondExported = array_combine($headers, $secondRow);
        $this->assertIsArray($secondExported);
        $this->assertSame('2', $secondExported['No']);
    }

    public function test_admin_can_download_a_filtered_excel_workbook(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'CA Morowali']);
        $desa = Desa::create(['kawasan_id' => $kawasan->id, 'nama_desa' => 'Desa Excel']);
        $this->createGroup($kawasan, $desa, 'Kelompok Excel Target', [
            'tahun_pembentukan' => 2017,
            'tahun_monitoring' => 2026,
            'catatan_kebutuhan_hasil_monev' => 'Catatan <lengkap> & aman',
        ]);
        $this->createGroup($kawasan, $desa, 'Kelompok Excel Target Kedua');
        $this->createGroup($kawasan, $desa, 'Kelompok Excel Lain');

        $response = $this->actingAs($admin)
            ->get(route('kelompok.export', ['format' => 'xlsx', 'search' => 'Excel Target']));

        $response->assertDownload('kelompok-binaan.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $workbook = $response->streamedContent();
        $this->assertStringStartsWith('PK', $workbook);

        $path = tempnam(sys_get_temp_dir(), 'kelompok-export-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $workbook);
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);
        $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
        foreach ([
            '[Content_Types].xml',
            '_rels/.rels',
            'xl/workbook.xml',
            'xl/_rels/workbook.xml.rels',
            'xl/styles.xml',
            'xl/worksheets/sheet1.xml',
        ] as $part) {
            $xml = $archive->getFromName($part);
            $this->assertIsString($xml);
            $this->assertNotFalse(simplexml_load_string($xml));
        }
        $archive->close();
        unlink($path);

        $this->assertIsString($sheet);
        $this->assertStringContainsString('Tahun Pembentukan', $sheet);
        $this->assertStringContainsString('Tahun Monitoring', $sheet);
        $this->assertStringContainsString('Status Kelompok', $sheet);
        $this->assertStringContainsString('Aktif', $sheet);
        $this->assertStringContainsString('Catatan Kebutuhan / Hasil Monev', $sheet);
        $this->assertStringContainsString('Kelompok Excel Target', $sheet);
        $this->assertStringContainsString('Catatan &lt;lengkap&gt; &amp; aman', $sheet);
        $this->assertStringContainsString('<c r="A1" s="1" t="inlineStr">', $sheet);
        $this->assertStringContainsString('>No</t>', $sheet);
        $this->assertStringContainsString('r="A2" s="2"><v>1</v>', $sheet);
        $this->assertStringContainsString('r="A3" s="2"><v>2</v>', $sheet);
        $this->assertStringContainsString('<c r="B1" s="1" t="inlineStr">', $sheet);
        $this->assertStringContainsString('>Nama Kawasan</t>', $sheet);
        $this->assertStringContainsString('<c r="C1" s="1" t="inlineStr">', $sheet);
        $this->assertStringContainsString('>Nama Desa</t>', $sheet);
        $this->assertStringNotContainsString('ID Kelompok', $sheet);
        $this->assertStringNotContainsString('ID Kawasan', $sheet);
        $this->assertStringNotContainsString('ID Desa', $sheet);
        $this->assertStringNotContainsString('Kelompok Excel Lain', $sheet);
    }

    public function test_group_exports_are_only_available_to_admins(): void
    {
        $this->get(route('kelompok.export', ['format' => 'csv']))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('kelompok.export', ['format' => 'csv']))
            ->assertForbidden();
    }

    public function test_admin_group_page_offers_both_export_formats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('kelompok.index'))
            ->assertOk()
            ->assertSee('Unduh CSV')
            ->assertSee('Unduh Excel')
            ->assertSee(route('kelompok.export', ['format' => 'csv']), false)
            ->assertSee(route('kelompok.export', ['format' => 'xlsx']), false);
    }

    public function test_csv_keeps_formula_like_notes_as_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create(['kawasan_id' => $kawasan->id, 'nama_desa' => 'Desa Aman']);
        $this->createGroup($kawasan, $desa, 'Kelompok Aman', [
            'catatan_kebutuhan_hasil_monev' => '=HYPERLINK("https://example.test","open")',
        ]);

        $csv = $this->actingAs($admin)
            ->get(route('kelompok.export', ['format' => 'csv', 'search' => 'Kelompok Aman']))
            ->streamedContent();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($csv, 3));
        rewind($stream);
        $headers = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        fclose($stream);

        $this->assertIsArray($headers);
        $this->assertIsArray($row);
        $exported = array_combine($headers, $row);
        $this->assertIsArray($exported);
        $this->assertSame('\'=HYPERLINK("https://example.test","open")', $exported['Catatan Kebutuhan / Hasil Monev']);
    }

    public function test_search_wildcards_are_treated_as_literal_text_during_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create(['kawasan_id' => $kawasan->id, 'nama_desa' => 'Desa Tanda']);
        $this->createGroup($kawasan, $desa, 'Kelompok 100% Aman');
        $this->createGroup($kawasan, $desa, 'Kelompok 100 Aman');

        $csv = $this->actingAs($admin)
            ->get(route('kelompok.export', ['format' => 'csv', 'search' => '100%']))
            ->streamedContent();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($csv, 3));
        rewind($stream);
        $headers = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        $empty = fgetcsv($stream, 0, ',', '"', '');
        fclose($stream);

        $this->assertIsArray($headers);
        $this->assertIsArray($row);
        $this->assertFalse($empty);
        $exported = array_combine($headers, $row);
        $this->assertIsArray($exported);
        $this->assertSame('Kelompok 100% Aman', $exported['Nama Kelompok']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createGroup(Kawasan $kawasan, Desa $desa, string $name, array $attributes = []): Kelompok
    {
        return Kelompok::create(array_merge([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => $name,
            'status_kelompok' => 'Aktif',
            'nama_ketua' => 'Ketua '.$name,
            'no_hp' => '08000000000',
            'jenis_usaha' => 'Pertanian',
            'produk_unggulan' => 'Kakao',
            'sk_kelompok' => 'SK-001',
            'tahun_pembinaan' => 2025,
            'jumlah_anggota_laki_laki' => 8,
            'jumlah_anggota_perempuan' => 6,
            'jumlah_anggota' => 14,
            'jumlah_bantuan' => '25000000.00',
            'latitude' => '-0.9000000',
            'longitude' => '119.8000000',
        ], $attributes));
    }
}
