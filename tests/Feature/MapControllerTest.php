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

    public function test_map_shows_only_the_basemap_without_deleting_saved_group_coordinates(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_kelompok' => 'Kelompok Peta Lestari',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'kecamatan' => 'Bualemo',
                'kabupaten' => 'Banggai',
                'latitude' => '-0.9012000',
                'longitude' => '119.8701000',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Peta Lestari',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);

        $mapResponse = $this->get(route('kelompok.map'));

        $mapResponse->assertOk()
            ->assertSee('Tampilan basemap tanpa poligon kawasan maupun titik kelompok.')
            ->assertDontSee('Kelompok Peta Lestari')
            ->assertSee('leaflet@1.9.4/dist/leaflet.js', false)
            ->assertSee('group-map')
            ->assertDontSee('group-map-locations');

        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Peta Lestari',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_group_coordinates_must_be_provided_together(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_kelompok' => 'Kelompok Tanpa Koordinat Lengkap',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
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

        $response = $this->actingAs(User::factory()->create())
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_kelompok' => 'Kelompok Koordinat Tidak Valid',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
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
        $user = User::factory()->create();
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);

        $this->actingAs($user)
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'nama_kelompok' => 'Kelompok Peta Edit',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
            ]);
        $kelompokId = (int) Kelompok::query()
            ->where('nama_kelompok', 'Kelompok Peta Edit')
            ->value('id');

        $response = $this->put(route('kelompok.update', $kelompokId), [
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Peta Edit',
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

    public function test_map_redirects_guests_to_login(): void
    {
        $response = $this->get(route('kelompok.map'));

        $response->assertRedirect(route('login'));
    }
}
