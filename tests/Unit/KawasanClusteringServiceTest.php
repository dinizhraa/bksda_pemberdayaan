<?php

namespace Tests\Unit;

use App\Services\KawasanClusteringService;
use PHPUnit\Framework\TestCase;

class KawasanClusteringServiceTest extends TestCase
{
    public function test_it_normalizes_clusters_and_validates_kawasan_data(): void
    {
        $rows = [
            $this->row('Kawasan A', 10_000_000, 4, 1),
            $this->row('Kawasan B', 12_000_000, 5, 2),
            $this->row('Kawasan C', 15_000_000, 6, 3),
            $this->row('Kawasan D', 400_000_000, 20, 10),
            $this->row('Kawasan E', 450_000_000, 22, 12),
            $this->row('Kawasan F', 500_000_000, 24, 15),
            $this->row('Data tidak valid', 20_000_000, 0, 0),
        ];

        $result = (new KawasanClusteringService)->cluster($rows);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(6, $result['sample_count']);
        $this->assertSame(1, $result['excluded_count']);
        $this->assertCount(3, $result['clusters']);
        $this->assertCount(6, $result['points']);
        $this->assertNotNull($result['silhouette']);
        $this->assertContains('NTE Rendah', array_column($result['clusters'], 'name'));
        $this->assertContains('NTE Tinggi', array_column($result['clusters'], 'name'));
    }

    public function test_it_does_not_claim_a_cluster_result_for_insufficient_or_identical_data(): void
    {
        $service = new KawasanClusteringService;

        $tooSmall = $service->cluster([
            $this->row('Kawasan A', 10_000_000, 4, 1),
            $this->row('Kawasan B', 20_000_000, 5, 2),
        ]);
        $identical = $service->cluster([
            $this->row('Kawasan A', 10_000_000, 4, 1),
            $this->row('Kawasan B', 10_000_000, 4, 1),
            $this->row('Kawasan C', 10_000_000, 4, 1),
        ]);

        $this->assertSame('insufficient_samples', $tooSmall['status']);
        $this->assertSame('insufficient_variation', $identical['status']);
        $this->assertSame([], $tooSmall['clusters']);
        $this->assertNull($identical['silhouette']);
    }

    private function row(string $name, int $nte, int $villages, int $facilitated): array
    {
        return [
            'nama_kawasan' => $name,
            'total_nte' => $nte,
            'jumlah_desa_sekitar' => $villages,
            'jumlah_desa_difasilitasi' => $facilitated,
        ];
    }
}
