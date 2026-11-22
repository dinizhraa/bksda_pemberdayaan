<?php

namespace Tests\Feature;

use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_assets_use_esri_satellite_street_controls_without_polygon_layers(): void
    {
        $mapScript = file_get_contents(resource_path('js/map.js'));
        $mapView = file_get_contents(resource_path('views/peta-kelompok.blade.php'));

        $this->assertIsString($mapScript);
        $this->assertIsString($mapView);
        $this->assertStringContainsString('World_Imagery/MapServer/tile', $mapScript);
        $this->assertStringContainsString('World_Boundaries_and_Places/MapServer/tile', $mapScript);
        $this->assertStringContainsString("'Satellite'", $mapScript);
        $this->assertStringContainsString("'Street Map'", $mapScript);
        $this->assertStringNotContainsString('window.L.polygon', $mapScript);
        $this->assertStringNotContainsString('window.L.geoJSON', $mapScript);
        $this->assertStringNotContainsString('Polygon Kawasan', $mapView);
    }

    public function test_map_uses_red_markers_and_only_requested_popup_details(): void
    {
        $mapScript = file_get_contents(resource_path('js/map.js'));

        $this->assertIsString($mapScript);
        $this->assertStringContainsString('window.L.divIcon', $mapScript);
        $this->assertStringContainsString('fill="#dc2626"', $mapScript);
        $this->assertStringContainsString("String(value ?? '-')", $mapScript);
        $this->assertStringContainsString('escapeHtml(location.tahun_pembinaan)', $mapScript);
        $this->assertStringContainsString('escapeHtml(location.tahun_pembentukan)', $mapScript);
        $this->assertStringContainsString('escapeHtml(location.tahun_monitoring)', $mapScript);

        foreach ([
            '<dt>Tahun Pembinaan</dt>',
            '<dt>Tahun Pembentukan</dt>',
            '<dt>Tahun Monitoring</dt>',
            '<dt>Kawasan</dt>',
            '<dt>Desa</dt>',
            '<dt>Kecamatan</dt>',
            '<dt>Kabupaten</dt>',
            '<dt>Jenis Usaha</dt>',
            '<dt>Jumlah Anggota Laki-laki</dt>',
            '<dt>Jumlah Anggota Perempuan</dt>',
            '<dt>Status Kelompok</dt>',
        ] as $field) {
            $this->assertStringContainsString($field, $mapScript);
        }

        foreach ([
            '<dt>Ketua</dt>',
            '<dt>Kontak</dt>',
            '<dt>SK Kelompok</dt>',
            '<dt>Anggota</dt>',
            '<dt>Bantuan</dt>',
            '<dt>Koordinat</dt>',
        ] as $field) {
            $this->assertStringNotContainsString($field, $mapScript);
        }
    }

    public function test_map_shows_saved_group_coordinates_without_deleting_them(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_desa' => 'Desa Peta',
                'nama_kelompok' => 'Kelompok Peta Lestari',
                'status_kelompok' => 'Aktif',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'tahun_pembinaan' => 2019,
                'tahun_pembentukan' => 2020,
                'tahun_monitoring' => 2025,
                'jumlah_bantuan' => 25000000,
                'jumlah_anggota_laki_laki' => 0,
                'jumlah_anggota_perempuan' => 0,
                'kecamatan' => 'Bualemo',
                'kabupaten' => 'Banggai',
                'latitude' => '-0.9012000',
                'longitude' => '119.8701000',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Peta Lestari',
            'status_kelompok' => 'Aktif',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);

        $mapResponse = $this->get(route('kelompok.map'));

        $mapResponse->assertOk()
            ->assertSee('Lokasi kelompok binaan yang tersimpan')
            ->assertSee('Kelompok Peta Lestari')
            ->assertSee('Aktif')
            ->assertSee('leaflet@1.9.4/dist/leaflet.js', false)
            ->assertSee('group-map')
            ->assertSee('data-locations=', false);
        $locations = $mapResponse->viewData('locations');
        $this->assertSame('Aktif', $locations->first()['status_kelompok']);
        $this->assertSame(2019, $locations->first()['tahun_pembinaan']);
        $this->assertSame(2020, $locations->first()['tahun_pembentukan']);
        $this->assertSame(2025, $locations->first()['tahun_monitoring']);
        $this->assertArrayNotHasKey('nama_ketua', $locations->first());
        $this->assertArrayNotHasKey('no_hp', $locations->first());
        $this->assertArrayNotHasKey('sk_kelompok', $locations->first());
        $this->assertArrayNotHasKey('jumlah_anggota', $locations->first());
        $this->assertArrayNotHasKey('jumlah_bantuan', $locations->first());

        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Peta Lestari',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_map_keeps_year_fields_separate_when_formation_year_is_missing(): void
    {
        Kelompok::create([
            'nama_kelompok' => 'Kelompok Tahun Lama',
            'tahun_pembinaan' => 2026,
            'latitude' => '-0.9012000',
            'longitude' => '119.8701000',
        ]);

        $locations = $this->get(route('kelompok.map'))->viewData('locations');
        $location = $locations->firstWhere('nama_kelompok', 'Kelompok Tahun Lama');

        $this->assertNotNull($location);
        $this->assertSame(2026, $location['tahun_pembinaan']);
        $this->assertNull($location['tahun_pembentukan']);
        $this->assertNull($location['tahun_monitoring']);
    }

    public function test_group_coordinates_must_be_provided_together(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_desa' => 'Desa Peta',
                'nama_kelompok' => 'Kelompok Tanpa Koordinat Lengkap',
                'status_kelompok' => 'Aktif',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'jumlah_anggota_laki_laki' => 0,
                'jumlah_anggota_perempuan' => 0,
                'latitude' => '-0.9012',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('longitude');
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Tanpa Koordinat Lengkap',
        ]);
    }

    public function test_group_coordinates_must_be_within_valid_geographic_ranges(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_desa' => 'Desa Peta',
                'nama_kelompok' => 'Kelompok Koordinat Tidak Valid',
                'status_kelompok' => 'Aktif',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'jumlah_anggota_laki_laki' => 0,
                'jumlah_anggota_perempuan' => 0,
                'latitude' => '-91',
                'longitude' => '119.8701',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('latitude');
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Koordinat Tidak Valid',
        ]);
    }

    public function test_group_coordinates_can_be_updated_from_the_edit_form(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $this->actingAs($user)
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_desa' => 'Desa Peta',
                'nama_kelompok' => 'Kelompok Peta Edit',
                'status_kelompok' => 'Aktif',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'jumlah_anggota_laki_laki' => 0,
                'jumlah_anggota_perempuan' => 0,
            ]);
        $kelompokId = (int) Kelompok::query()
            ->where('nama_kelompok', 'Kelompok Peta Edit')
            ->value('id');

        $response = $this->put(route('kelompok.update', $kelompokId), [
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Peta',
            'nama_kelompok' => 'Kelompok Peta Edit',
            'status_kelompok' => 'Aktif',
            'nama_ketua' => 'Ketua Peta',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 25000000,
            'latitude' => '-0.9012',
            'longitude' => '119.8701',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompokId,
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_map_is_publicly_available_to_guests(): void
    {
        $response = $this->get(route('kelompok.map'));

        $response->assertOk();
    }
}
