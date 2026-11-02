<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_kawasan_csv(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'kawasan_');
        file_put_contents($file, "nama_kawasan,fungsi_kawasan,jumlah_desa_sekitar,jumlah_desa_difasilitasi\nCA Morowali,CA,17,10\nCA Pamona,CA,11,10\n");

        $this->artisan('data:import', ['type' => 'kawasan', 'path' => $file])
            ->assertSuccessful();

        $this->assertDatabaseHas('kawasans', ['nama_kawasan' => 'CA Morowali']);
        $this->assertDatabaseHas('kawasans', ['nama_kawasan' => 'CA Pamona']);

        unlink($file);
    }
}
