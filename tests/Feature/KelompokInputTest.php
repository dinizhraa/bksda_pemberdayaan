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

    public function test_admin_can_create_group_with_village_sk_and_calculated_gender_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $formResponse = $this->actingAs($admin)
            ->get(route('kelompok.create'))
            ->assertOk()
            ->assertSee('name="nama_desa" type="text"', false)
            ->assertSee('SK (Surat Keputusan)')
            ->assertSee('Jumlah Anggota Laki-laki')
            ->assertSee('Jumlah Anggota Perempuan')
            ->assertSee('Jumlah Anggota Kelompok')
            ->assertSee('updateTotal', false);

        $html = $formResponse->getContent();
        $desaInput = $this->inputElement($html, 'nama_desa');
        $maleInput = $this->inputElement($html, 'jumlah_anggota_laki_laki');
        $femaleInput = $this->inputElement($html, 'jumlah_anggota_perempuan');
        $totalInput = $this->inputElement($html, 'jumlah_anggota');

        $this->assertSame('text', $desaInput->getAttribute('type'));
        $this->assertTrue($desaInput->hasAttribute('required'));
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
            'jumlah_anggota_laki_laki' => 8,
            'jumlah_anggota_perempuan' => 5,
            'jumlah_anggota' => 999,
        ]));

        $response->assertRedirect(route('dashboard'));
        $kelompok = Kelompok::query()->where('nama_kelompok', 'Kelompok Uji')->firstOrFail();
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Uji',
            'desa_id' => $kelompok->desa_id,
            'sk_kelompok' => 'SK-2026-001',
            'jumlah_anggota_laki_laki' => 8,
            'jumlah_anggota_perempuan' => 5,
            'jumlah_anggota' => 13,
        ]);
        $this->assertDatabaseHas('desas', [
            'id' => $kelompok->desa_id,
            'nama_desa' => 'Desa Uji Manual',
            'kawasan_id' => $kawasan->id,
        ]);

        $this->get(route('kelompok.index'))
            ->assertOk()
            ->assertSee('Desa Uji Manual')
            ->assertSee('SK-2026-001')
            ->assertSee('Laki-laki')
            ->assertSee('Perempuan')
            ->assertSee('Total Anggota')
            ->assertSee('<td>8</td>', false)
            ->assertSee('<td>5</td>', false)
            ->assertSee('<td>13</td>', false);
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

    /**
     * @return array<string, int|string>
     */
    private function groupPayload(Kawasan $kawasan): array
    {
        return [
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Uji',
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
