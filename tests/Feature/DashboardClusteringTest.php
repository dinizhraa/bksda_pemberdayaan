<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use Database\Seeders\BksdaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardClusteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_visualization_displays_real_clustering_results_for_valid_kawasan_data(): void
    {
        $this->seed(BksdaSeeder::class);

        $response = $this->get(route('visualisasi'));

        $response->assertOk()
            ->assertSee('Data Mining: K-Means Clustering Kawasan')
            ->assertSee('Validasi silhouette')
            ->assertSee('NTE Rendah')
            ->assertSee('NTE Tinggi')
            ->assertSee('chart-cluster');
        $response->assertViewHas('clusterAnalysis', fn (array $analysis): bool => $analysis['status'] === 'ok'
            && $analysis['sample_count'] === 18
            && count($analysis['clusters']) === 3
            && array_sum(array_column($analysis['clusters'], 'count')) === 18
            && $analysis['silhouette'] !== null);
    }

    public function test_public_visualization_shows_the_pdf_summary_and_unallocated_difference(): void
    {
        $this->seed(BksdaSeeder::class);

        $response = $this->get(route('visualisasi'));

        $response->assertOk()
            ->assertSee('Rp 1,54 M')
            ->assertSee('Selisih NTE langsung yang belum teralokasi ke kawasan:')
            ->assertSee('Rp 6.600.000');
    }

    public function test_public_visualization_displays_kecamatan_and_kabupaten_from_the_related_desa(): void
    {
        $this->seed(BksdaSeeder::class);
        $kawasan = Kawasan::where('nama_kawasan', 'SM Bakiriang')->firstOrFail();
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Uji Lokasi',
            'kecamatan' => 'Kecamatan Uji Lokasi',
            'kabupaten' => 'Kabupaten Uji Lokasi',
        ]);
        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Uji Lokasi',
        ]);

        $response = $this->get(route('visualisasi'));

        $response->assertOk()
            ->assertSee('Kecamatan Uji Lokasi')
            ->assertSee('Kabupaten Uji Lokasi');
    }
}
