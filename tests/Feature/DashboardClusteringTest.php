<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BksdaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardClusteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_real_clustering_results_for_valid_kawasan_data(): void
    {
        $this->seed(BksdaSeeder::class);

        $response = $this->actingAs(User::factory()->create())
            ->get('/dashboard');

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
}
