<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelompokInputTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_GROUP_HEADERS = [
        'No',
        'Tahun Pembinaan',
        'Tahun Pembentukan',
        'Tahun Monitoring',
        'Status Kelompok',
        'Kawasan',
        'Desa',
        'Kecamatan',
        'Kabupaten',
        'Nama Kelompok',
        'Nama Ketua',
        'No HP',
        'Latitude',
        'Longitude',
        'Jenis Usaha',
        'SK Kelompok',
        'Jumlah Anggota Laki-laki',
        'Jumlah Anggota Perempuan',
        'Total Anggota',
        'Jumlah Bantuan (Rp)',
        'Catatan Kebutuhan / Hasil Monev',
        'Aksi',
    ];

    public function test_admin_can_create_group_with_village_sk_and_calculated_gender_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $formResponse = $this->actingAs($admin)
            ->get(route('kelompok.create'))
            ->assertOk()
            ->assertSee('name="nama_desa" type="text"', false)
            ->assertSee('name="status_kelompok"', false)
            ->assertSee('value="Aktif"', false)
            ->assertSee('value="Tidak Aktif"', false)
            ->assertSee('SK (Surat Keputusan)')
            ->assertSee('Jumlah Anggota Laki-laki')
            ->assertSee('Jumlah Anggota Perempuan')
            ->assertSee('Jumlah Anggota Kelompok')
            ->assertSee('Tahun Pembinaan')
            ->assertSee('Tahun Pembentukan Kelompok')
            ->assertSee('Tahun Monitoring')
            ->assertSee('updateTotal', false);

        $html = $formResponse->getContent();
        $desaInput = $this->inputElement($html, 'nama_desa');
        $trainingYearInput = $this->inputElement($html, 'tahun_pembinaan');
        $maleInput = $this->inputElement($html, 'jumlah_anggota_laki_laki');
        $femaleInput = $this->inputElement($html, 'jumlah_anggota_perempuan');
        $totalInput = $this->inputElement($html, 'jumlah_anggota');

        $this->assertSame('text', $desaInput->getAttribute('type'));
        $this->assertTrue($desaInput->hasAttribute('required'));
        $this->assertSame('number', $trainingYearInput->getAttribute('type'));
        $this->assertSame('number', $maleInput->getAttribute('type'));
        $this->assertSame('number', $femaleInput->getAttribute('type'));
        foreach ([$maleInput, $femaleInput] as $input) {
            $this->assertFalse($input->hasAttribute('disabled'));
            $this->assertFalse($input->hasAttribute('readonly'));
        }
        $this->assertTrue($totalInput->hasAttribute('readonly'));

        $response = $this->post(route('kelompok.store'), array_merge($this->groupPayload($kawasan), [
            'nama_desa' => 'Desa Uji Manual',
            'sk_kelompok' => 'SK-2026-001',
            'tahun_pembinaan' => 2024,
            'tahun_pembentukan' => 2019,
            'tahun_monitoring' => 2025,
            'no_hp' => '08123456789',
            'kecamatan' => 'Bualemo',
            'kabupaten' => 'Banggai',
            'latitude' => '-0.9012',
            'longitude' => '119.8701',
            'catatan_kebutuhan_hasil_monev' => 'Catatan kelompok',
            'status_kelompok' => 'Aktif',
            'jumlah_anggota_laki_laki' => 8,
            'jumlah_anggota_perempuan' => 5,
            'jumlah_anggota' => 999,
        ]));

        $response->assertRedirect(route('dashboard'));
        $kelompok = Kelompok::query()->where('nama_kelompok', 'Kelompok Uji')->firstOrFail();
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Uji',
            'desa_id' => $kelompok->desa_id,
            'nama_ketua' => 'Ketua Uji',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 25000000,
            'sk_kelompok' => 'SK-2026-001',
            'tahun_pembinaan' => 2024,
            'tahun_pembentukan' => 2019,
            'tahun_monitoring' => 2025,
            'no_hp' => '08123456789',
            'kecamatan' => 'Bualemo',
            'kabupaten' => 'Banggai',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
            'catatan_kebutuhan_hasil_monev' => 'Catatan kelompok',
            'status_kelompok' => 'Aktif',
            'jumlah_anggota_laki_laki' => 8,
            'jumlah_anggota_perempuan' => 5,
            'jumlah_anggota' => 13,
        ]);
        $this->assertDatabaseHas('desas', [
            'id' => $kelompok->desa_id,
            'nama_desa' => 'Desa Uji Manual',
            'kawasan_id' => $kawasan->id,
        ]);

        $tableResponse = $this->get(route('kelompok.index'))->assertOk();
        $tableDocument = new \DOMDocument;
        $tableDocument->loadHTML($tableResponse->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $tableHeaders = (new \DOMXPath($tableDocument))
            ->query('//table[@id="kelompokTable"]/thead/tr/th');
        $this->assertNotFalse($tableHeaders);
        $tableHeaderLabels = [];
        foreach ($tableHeaders as $header) {
            $tableHeaderLabels[] = trim($header->textContent);
        }
        $this->assertSame(self::EXPECTED_GROUP_HEADERS, $tableHeaderLabels);
        $tableResponse
            ->assertSee('class="overflow-x-auto px-3 pb-3"', false)
            ->assertSee('class="glass-table min-w-max"', false)
            ->assertSee('class="w-36 whitespace-nowrap px-4 py-3 text-center">Aksi</th>', false)
            ->assertSee('inline-flex items-center gap-1.5 whitespace-nowrap shrink-0', false)
            ->assertSee('fa-location-dot shrink-0 text-rose-500', false);
        $tableCells = (new \DOMXPath($tableDocument))
            ->query('//table[@id="kelompokTable"]/tbody/tr[1]/td');
        $this->assertNotFalse($tableCells);
        $tableCellValues = [];
        foreach ($tableCells as $cell) {
            $tableCellValues[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
        }
        $this->assertCount(count(self::EXPECTED_GROUP_HEADERS), $tableCellValues);
        $this->assertSame(['1', '2024', '2019', '2025', 'Aktif'], array_slice($tableCellValues, 0, 5));
        $this->assertSame('SM Bakiriang', $tableCellValues[5]);
        $this->assertSame('Desa Uji Manual', $tableCellValues[6]);
        $this->assertSame('Bualemo', $tableCellValues[7]);
        $this->assertSame('Banggai', $tableCellValues[8]);
        $this->assertSame('8', $tableCellValues[16]);
        $this->assertSame('5', $tableCellValues[17]);
        $this->assertSame('13', $tableCellValues[18]);
        $tableResponse
            ->assertSee('Desa Uji Manual')
            ->assertSee('SK-2026-001')
            ->assertDontSee('ID: KLP-00')
            ->assertSee('2024')
            ->assertSee('2019')
            ->assertSee('2025')
            ->assertSee('Bualemo')
            ->assertSee('Banggai')
            ->assertSee('08123456789')
            ->assertSee('-0.9012')
            ->assertSee('119.8701')
            ->assertSee('Budidaya ikan')
            ->assertSee('Catatan kelompok');
    }

    public function test_admin_can_edit_group_gender_counts_and_recalculate_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Uji',
        ]);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Uji',
            'jumlah_anggota' => 3,
        ]);

        $editFormResponse = $this->actingAs($admin)
            ->get(route('kelompok.edit', $kelompok->id))
            ->assertOk()
            ->assertSee('value="Desa Uji"', false);
        $editForm = $editFormResponse->getContent();
        $this->assertSame(
            'number',
            $this->inputElement($editForm, 'jumlah_anggota_laki_laki')->getAttribute('type'),
        );
        $this->assertFalse($this->inputElement($editForm, 'jumlah_anggota_laki_laki')->hasAttribute('readonly'));
        $this->assertFalse($this->inputElement($editForm, 'jumlah_anggota_perempuan')->hasAttribute('readonly'));

        $response = $this->actingAs($admin)->put(route('kelompok.update', $kelompok->id), array_merge(
            $this->groupPayload($kawasan),
            [
                'nama_desa' => 'Desa Uji',
                'sk_kelompok' => 'SK-EDIT-001',
                'jumlah_anggota_laki_laki' => 4,
                'jumlah_anggota_perempuan' => 6,
            ],
        ));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'sk_kelompok' => 'SK-EDIT-001',
            'status_kelompok' => 'Aktif',
            'jumlah_anggota_laki_laki' => 4,
            'jumlah_anggota_perempuan' => 6,
            'jumlah_anggota' => 10,
        ]);
        $this->assertSame('Desa Uji', $kelompok->fresh()->desa?->nama_desa);
        $this->assertDatabaseCount('desas', 1);
    }

    public function test_editing_legacy_group_without_gender_counts_preserves_existing_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Lama',
            'jumlah_anggota' => 12,
        ]);

        $response = $this->actingAs($admin)->put(route('kelompok.update', $kelompok->id), array_merge(
            $this->groupPayload($kawasan),
            [
                'jumlah_anggota_laki_laki' => null,
                'jumlah_anggota_perempuan' => null,
            ],
        ));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'jumlah_anggota_laki_laki' => null,
            'jumlah_anggota_perempuan' => null,
            'jumlah_anggota' => 12,
        ]);
    }

    public function test_group_update_requires_both_gender_counts_when_one_is_provided(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Lama',
            'jumlah_anggota' => 12,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('kelompok.edit', $kelompok->id))
            ->put(route('kelompok.update', $kelompok->id), array_merge($this->groupPayload($kawasan), [
                'jumlah_anggota_laki_laki' => 3,
            ]));

        $response->assertRedirect(route('kelompok.edit', $kelompok->id))
            ->assertSessionHasErrors('jumlah_anggota_perempuan');
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Lama',
            'jumlah_anggota' => 12,
        ]);
    }

    public function test_group_creation_rejects_negative_or_incomplete_gender_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs($admin)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), array_merge($this->groupPayload($kawasan), [
                'nama_desa' => '',
                'jumlah_anggota_laki_laki' => -1,
            ]));

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors(['nama_desa', 'jumlah_anggota_laki_laki', 'jumlah_anggota_perempuan']);
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Uji',
        ]);
    }

    public function test_group_monitoring_fields_can_be_created_edited_and_viewed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $catatan = "Kebutuhan akses air bersih.\nTindak lanjut: koordinasi dengan desa.";

        $formResponse = $this->actingAs($admin)
            ->get(route('kelompok.create'))
            ->assertOk()
            ->assertSee('Tahun Pembentukan Kelompok')
            ->assertSee('Tahun Monitoring')
            ->assertSee('Catatan Kebutuhan / Hasil Monev');
        $formHtml = $formResponse->getContent();
        $this->assertSame(
            'number',
            $this->inputElement($formHtml, 'tahun_pembentukan')->getAttribute('type'),
        );
        $this->assertSame(
            'number',
            $this->inputElement($formHtml, 'tahun_monitoring')->getAttribute('type'),
        );
        $document = new \DOMDocument;
        $document->loadHTML($formHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
        $textareas = (new \DOMXPath($document))
            ->query('//textarea[@name="catatan_kebutuhan_hasil_monev"]');
        $this->assertNotFalse($textareas);
        $this->assertSame(
            1,
            $textareas->length,
        );

        $this->post(route('kelompok.store'), array_merge($this->groupPayload($kawasan), [
            'tahun_pembinaan' => 2017,
            'tahun_pembentukan' => 2018,
            'tahun_monitoring' => 2025,
            'catatan_kebutuhan_hasil_monev' => $catatan,
            'jumlah_anggota_laki_laki' => 4,
            'jumlah_anggota_perempuan' => 3,
        ]))->assertRedirect(route('dashboard'));

        $kelompok = Kelompok::query()->where('nama_kelompok', 'Kelompok Uji')->firstOrFail();
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'tahun_pembinaan' => 2017,
            'tahun_pembentukan' => 2018,
            'tahun_monitoring' => 2025,
            'catatan_kebutuhan_hasil_monev' => $catatan,
        ]);

        $this->get(route('kelompok.edit', $kelompok->id))
            ->assertOk()
            ->assertSee('value="2017"', false)
            ->assertSee('value="2018"', false)
            ->assertSee('value="2025"', false)
            ->assertSee(e($catatan), false);

        $this->put(route('kelompok.update', $kelompok->id), array_merge($this->groupPayload($kawasan), [
            'status_kelompok' => 'Tidak Aktif',
            'tahun_pembinaan' => 2020,
            'tahun_pembentukan' => 2019,
            'tahun_monitoring' => 2026,
            'catatan_kebutuhan_hasil_monev' => 'Kendala pemasaran; tindak lanjut pelatihan produk.',
            'jumlah_anggota_laki_laki' => 4,
            'jumlah_anggota_perempuan' => 3,
        ]))->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'status_kelompok' => 'Tidak Aktif',
            'tahun_pembinaan' => 2020,
            'tahun_pembentukan' => 2019,
            'tahun_monitoring' => 2026,
            'catatan_kebutuhan_hasil_monev' => 'Kendala pemasaran; tindak lanjut pelatihan produk.',
        ]);

        $this->get(route('kelompok.show', $kelompok->id))
            ->assertOk()
            ->assertSee('Tahun Pembinaan')
            ->assertSee('2020')
            ->assertSee('Tahun Pembentukan')
            ->assertSee('2019')
            ->assertSee('Status Kelompok')
            ->assertSee('Tidak Aktif')
            ->assertSee('Tahun Monitoring')
            ->assertSee('2026')
            ->assertSee('Catatan Kebutuhan / Hasil Monev')
            ->assertSee('Kendala pemasaran; tindak lanjut pelatihan produk.');
    }

    public function test_group_monitoring_years_must_contain_four_digits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs($admin)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), array_merge($this->groupPayload($kawasan), [
                'tahun_pembentukan' => 999,
                'tahun_monitoring' => 2026,
                'jumlah_anggota_laki_laki' => 4,
                'jumlah_anggota_perempuan' => 3,
            ]));

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('tahun_pembentukan');
        $this->assertDatabaseMissing('kelompoks', ['nama_kelompok' => 'Kelompok Uji']);
    }

    public function test_group_status_rejects_values_outside_the_allowed_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs($admin)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), array_merge($this->groupPayload($kawasan), [
                'status_kelompok' => 'Ditangguhkan',
            ]));

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('status_kelompok');
        $this->assertDatabaseMissing('kelompoks', ['nama_kelompok' => 'Kelompok Uji']);
    }

    public function test_admin_group_actions_include_detail_edit_and_confirmed_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Aksi',
            'nama_ketua' => 'Ketua Aksi',
            'jenis_usaha' => 'Madu hutan',
            'jumlah_bantuan' => 10000000,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('kelompok.index'))
            ->assertOk()
            ->assertSee(route('kelompok.show', $kelompok->id), false)
            ->assertSee(route('kelompok.edit', $kelompok->id), false)
            ->assertSee(route('kelompok.destroy', $kelompok->id), false)
            ->assertSee("confirm('Yakin ingin menghapus data kelompok ini?')", false);

        $this->get(route('kelompok.show', $kelompok->id))
            ->assertOk()
            ->assertSee('Detail Kelompok Binaan')
            ->assertSee('Kelompok Aksi')
            ->assertSee('Nama Ketua')
            ->assertSee('Ketua Aksi')
            ->assertSee('Jenis Usaha')
            ->assertSee('Madu hutan')
            ->assertSee('Jumlah Bantuan');
    }

    /**
     * @return array<string, int|string>
     */
    private function groupPayload(Kawasan $kawasan): array
    {
        return [
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Uji',
            'status_kelompok' => 'Aktif',
            'nama_desa' => 'Desa Uji',
            'nama_ketua' => 'Ketua Uji',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 25000000,
        ];
    }

    private function inputElement(string $html, string $name): \DOMElement
    {
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $inputs = (new \DOMXPath($document))->query('//input[@name="'.$name.'"]');

        $this->assertNotFalse($inputs);
        $inputPosition = strpos($html, 'name="'.$name.'"');
        $this->assertSame(
            1,
            $inputs->length,
            substr($html, max(0, ($inputPosition === false ? 0 : $inputPosition) - 60), 180),
        );

        $input = $inputs->item(0);
        $this->assertInstanceOf(\DOMElement::class, $input);

        return $input;
    }
}
