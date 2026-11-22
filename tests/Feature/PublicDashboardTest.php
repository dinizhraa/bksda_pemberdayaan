<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use App\Models\NteSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_offers_admin_login_without_public_registration(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Lihat Visualisasi')
            ->assertSee('Masuk Admin')
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('/register', false);
    }

    public function test_public_visitor_can_open_visualization_without_login(): void
    {
        $this->get(route('visualisasi'))
            ->assertOk()
            ->assertSee('Pendampingan kelompok binaan untuk ekonomi masyarakat yang lestari')
            ->assertSee('Data Mining: K-Means Clustering Kawasan')
            ->assertSee('0 Kelompok')
            ->assertDontSee('Desa Terdaftar')
            ->assertDontSee('Bahari Abadi');
    }

    public function test_landing_statistics_reflect_current_groups_and_named_village_area_relations(): void
    {
        $kawasanSatu = Kawasan::create([
            'nama_kawasan' => 'Kawasan Statistik Satu',
            'jumlah_desa_sekitar' => 1,
            'jumlah_desa_difasilitasi' => 0,
        ]);
        $kawasanDua = Kawasan::create([
            'nama_kawasan' => 'Kawasan Statistik Dua',
            'jumlah_desa_sekitar' => 1,
            'jumlah_desa_difasilitasi' => 0,
        ]);
        Kelompok::create([
            'kawasan_id' => $kawasanSatu->id,
            'nama_kelompok' => 'Kelompok Statistik Satu',
        ]);

        $desaSatu = Desa::create([
            'kode_desa' => '7200010001',
            'nama_desa' => 'Desa Statistik Satu',
            'kecamatan' => 'Kecamatan Statistik',
            'kabupaten' => 'Kabupaten Statistik',
        ]);
        $desaSatu->kawasans()->attach([$kawasanSatu->id, $kawasanDua->id]);

        $desaDuplikat = Desa::create([
            'kode_desa' => '7200010001',
            'nama_desa' => 'Desa Statistik Satu',
            'kecamatan' => 'Kecamatan Statistik',
            'kabupaten' => 'Kabupaten Statistik',
        ]);
        $desaDuplikat->kawasans()->attach($kawasanSatu->id);

        $desaDua = Desa::create([
            'kode_desa' => '7200010002',
            'nama_desa' => 'Desa Statistik Dua',
            'kecamatan' => 'Kecamatan Statistik',
            'kabupaten' => 'Kabupaten Statistik',
        ]);
        $desaDua->kawasans()->attach($kawasanSatu->id);

        $danau = Desa::create([
            'kode_desa' => '7288888888',
            'nama_desa' => 'DANAU',
        ]);
        $danau->kawasans()->attach([$kawasanSatu->id, $kawasanDua->id]);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('jumlahKelompok', 1)
            ->assertViewHas('jumlahDesaPenyangga', 3)
            ->assertSee('Kelompok Binaan')
            ->assertSee('Desa Penyangga')
            ->assertSee('data-count="1" data-decimals="0">1</span>', false)
            ->assertSee('data-count="3" data-decimals="0">3</span>', false)
            ->assertSee('data-count="1"', false)
            ->assertSee('data-count="3"', false);

        $desaDua->update(['kode_desa' => null]);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('jumlahKelompok', 1)
            ->assertViewHas('jumlahDesaPenyangga', 2);

        $desaDua->update(['kode_desa' => '7200010002']);
        $kelompokDua = Kelompok::create([
            'kawasan_id' => $kawasanSatu->id,
            'nama_kelompok' => 'Kelompok Statistik Dua',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('jumlahKelompok', 2)
            ->assertViewHas('jumlahDesaPenyangga', 3);

        $kelompokDua->delete();
        $desaDua->kawasans()->detach($kawasanSatu->id);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('jumlahKelompok', 1)
            ->assertViewHas('jumlahDesaPenyangga', 2);
    }

    public function test_dashboard_statistics_follow_current_database_records(): void
    {
        $kawasan = Kawasan::create([
            'nama_kawasan' => 'Kawasan Statistik',
            'jumlah_desa_sekitar' => 1,
            'jumlah_desa_difasilitasi' => 1,
        ]);
        $nte = Nte::create([
            'kawasan_id' => $kawasan->id,
            'tahun' => 2025,
            'nte_langsung' => 60000,
            'nte_tidak_langsung' => 40000,
            'total_nte' => 100000,
        ]);
        Nte::create([
            'kawasan_id' => $kawasan->id,
            'tahun' => 2024,
            'nte_langsung' => 1000000,
            'nte_tidak_langsung' => 0,
            'total_nte' => 1000000,
        ]);
        NteSummary::create([
            'tahun' => 2025,
            'nte_langsung' => 60000,
            'nte_tidak_langsung' => 40000,
            'total_nte' => 999999,
            'nte_langsung_belum_teralokasi' => 5000,
        ]);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Statistik',
        ]);
        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Statistik',
        ]);

        $this->get(route('visualisasi'))
            ->assertOk()
            ->assertSee('Rp 105.000')
            ->assertSee('2 Kelompok')
            ->assertDontSee('Desa Terdaftar');

        $nte->update(['total_nte' => 150000]);
        $kelompok->delete();

        $this->get(route('visualisasi'))
            ->assertOk()
            ->assertSee('Rp 155.000')
            ->assertSee('1 Kelompok')
            ->assertDontSee('Desa Terdaftar');

        $nte->delete();

        $this->get(route('visualisasi'))
            ->assertOk()
            ->assertSee('Rp 5.000')
            ->assertSee('1 Kelompok')
            ->assertDontSee('Desa Terdaftar');
    }

    public function test_public_visitor_can_open_group_information_without_login(): void
    {
        $this->get(route('kelompok.index'))
            ->assertOk()
            ->assertSee('Daftar Kelompok Binaan')
            ->assertSee('id="groupSearch"', false)
            ->assertSee('Cari nama, desa, atau usaha')
            ->assertSee('id="exportKelompokCsv"', false)
            ->assertSee('id="exportKelompokExcel"', false)
            ->assertSee('id="exportKelompokPdf"', false)
            ->assertDontSee('Aksi');
    }

    public function test_public_visitor_can_open_conservation_area_information_without_login(): void
    {
        $this->get(route('kawasan.index'))
            ->assertOk()
            ->assertSee('Kawasan & NTE');
    }

    public function test_public_visitor_can_open_map_without_login(): void
    {
        $this->get(route('kelompok.map'))
            ->assertOk()
            ->assertSee('Peta Kawasan');
    }

    public function test_admin_dashboard_requires_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_create_group_data(): void
    {
        $response = $this->post(route('kelompok.store'), []);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('kelompoks', 0);
    }

    public function test_public_user_cannot_open_admin_create_page(): void
    {
        $this->get(route('kelompok.create'))
            ->assertRedirect(route('login'));
    }
}
