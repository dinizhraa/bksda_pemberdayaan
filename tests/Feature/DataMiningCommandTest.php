<?php

namespace Tests\Feature;

use App\Models\Kawasan;
use App\Models\Nte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataMiningCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_mining_command_generates_summary_csv(): void
    {
        $kawasanA = Kawasan::create(['nama_kawasan' => 'CA Morowali', 'fungsi_kawasan' => 'CA']);
        $kawasanB = Kawasan::create(['nama_kawasan' => 'CA Pamona', 'fungsi_kawasan' => 'CA']);

        Nte::create(['kawasan_id' => $kawasanA->id, 'tahun' => 2025, 'nte_langsung' => 5000000, 'nte_tidak_langsung' => 10000000, 'total_nte' => 15000000]);
        Nte::create(['kawasan_id' => $kawasanB->id, 'tahun' => 2025, 'nte_langsung' => 3000000, 'nte_tidak_langsung' => 2000000, 'total_nte' => 5000000]);

        $this->artisan('data:mine --csv')->assertSuccessful();

        $this->assertFileExists(storage_path('app/templates/nte_2025_summary.csv'));
    }
}
