<?php

namespace Tests\Feature;

use App\Models\Kawasan;
use App\Models\Nte;
use Database\Seeders\NteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_updates_kawasan_details_and_keeps_the_pdf_summary_gap_unallocated(): void
    {
        $morowali = Kawasan::query()->create([
            'nama_kawasan' => 'CA Morowali',
            'fungsi_kawasan' => 'CA',
            'jumlah_desa_sekitar' => 17,
            'jumlah_desa_difasilitasi' => 10,
        ]);
        Nte::query()->create([
            'kawasan_id' => $morowali->id,
            'tahun' => 2025,
            'nte_langsung' => 1,
            'nte_tidak_langsung' => 2,
            'total_nte' => 3,
        ]);
        $legacyKawasan = Kawasan::query()->create([
            'nama_kawasan' => 'CA Saluki',
            'fungsi_kawasan' => 'CA',
            'jumlah_desa_sekitar' => 7,
            'jumlah_desa_difasilitasi' => 5,
        ]);
        Nte::query()->create([
            'kawasan_id' => $legacyKawasan->id,
            'tahun' => 2025,
            'nte_langsung' => 15000000,
            'nte_tidak_langsung' => 25000000,
            'total_nte' => 40000000,
        ]);
        Kawasan::query()->create([
            'nama_kawasan' => 'SM Pinjan',
            'fungsi_kawasan' => 'SM',
            'jumlah_desa_sekitar' => 2,
            'jumlah_desa_difasilitasi' => 3,
        ]);

        $this->seed(NteSeeder::class);
        $this->seed(NteSeeder::class);

        $this->assertDatabaseCount('ntes', 18);
        $this->assertDatabaseHas('ntes', [
            'kawasan_id' => $morowali->id,
            'tahun' => 2025,
            'nte_langsung' => 64830000,
            'nte_tidak_langsung' => 119350000,
            'total_nte' => 184180000,
        ]);
        $this->assertDatabaseHas('kawasans', [
            'nama_kawasan' => 'SM Pinjan Tanjung Matop',
        ]);
        $this->assertDatabaseMissing('kawasans', [
            'nama_kawasan' => 'SM Pinjan',
        ]);
        $this->assertDatabaseMissing('ntes', [
            'kawasan_id' => $legacyKawasan->id,
            'tahun' => 2025,
        ]);
        $this->assertDatabaseHas('nte_summaries', [
            'tahun' => 2025,
            'nte_langsung' => 608320000,
            'nte_tidak_langsung' => 927489000,
            'total_nte' => 1535809000,
            'nte_langsung_belum_teralokasi' => 6600000,
        ]);

        $this->assertSame(601720000.0, (float) Nte::query()->sum('nte_langsung'));
    }
}
