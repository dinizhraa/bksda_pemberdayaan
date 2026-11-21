<?php

namespace Tests\Feature;

use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\NteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_pages_are_dedicated_and_the_correct_menu_is_active(): void
    {
        $this->seed(NteSeeder::class);
        $user = User::factory()->create(['role' => 'admin']);
        $kawasan = Kawasan::query()->where('nama_kawasan', 'CA Morowali')->firstOrFail();
        $kelompok = Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'tahun_pembinaan' => 2025,
            'nama_kelompok' => 'Kelompok Halaman Khusus',
        ]);
        $pages = [
            [route('dashboard'), 'Pendampingan kelompok binaan untuk ekonomi masyarakat yang lestari', 'dashboard'],
            [route('kelompok.index'), 'Daftar Kelompok Binaan', 'kelompok.index'],
            [route('kawasan.index'), 'Kawasan & NTE', 'kawasan.index'],
            [route('kelompok.map'), 'Peta Kawasan', 'kelompok.map'],
            [route('kelompok.create'), 'Tambah Kelompok Binaan', 'kelompok.create'],
            [route('profile.edit'), 'Kelola informasi akun', 'profile.edit'],
            [route('kelompok.edit', $kelompok->id), 'Edit Kelompok Binaan', 'kelompok.index'],
        ];

        foreach ($pages as [$url, $heading, $activeRoute]) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk()
                ->assertSee($heading)
                ->assertSee('href="'.route('kelompok.index').'"', false)
                ->assertSee('href="'.route('kawasan.index').'"', false)
                ->assertDontSee(route('dashboard').'#kelompok', false)
                ->assertDontSee(route('dashboard').'#kawasan', false);

            if ($url === route('kelompok.index')) {
                $response->assertSee('id="groupSearch"', false);
            }

            if ($url === route('dashboard')) {
                $response->assertDontSee('id="searchTable"', false)
                    ->assertDontSee('Tahun Anggaran 2026</span>', false)
                    ->assertDontSee('rounded-3xl border border-white/30 bg-white/95', false)
                    ->assertSee('/images/bksda-penyuluh.svg', false)
                    ->assertSee('Pendampingan kelompok binaan untuk ekonomi masyarakat yang lestari');
            }

            $this->assertMatchesRegularExpression(
                '/<a href="'.preg_quote(route($activeRoute), '/').'"\s+aria-current="page"\s+class="side-link side-link-active">/',
                $response->getContent(),
            );
            $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
        }

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Halaman Khusus',
        ]);
    }

    public function test_public_sidebar_links_to_public_pages_without_admin_access_links(): void
    {
        $response = $this->get(route('visualisasi'));

        $response->assertOk()
            ->assertSee('href="'.route('visualisasi').'"', false)
            ->assertSee('href="'.route('kelompok.index').'"', false)
            ->assertSee('href="'.route('kawasan.index').'"', false)
            ->assertSee('href="'.route('kelompok.map').'"', false)
            ->assertDontSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('href="'.route('login').'"', false)
            ->assertDontSee('/register', false);
    }

    public function test_area_page_renders_official_nte_rows_and_unallocated_summary(): void
    {
        $this->seed(NteSeeder::class);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('kawasan.index'));

        $response->assertOk()
            ->assertSee('CA Pamona')
            ->assertSee('Kawasan Unggulan')
            ->assertSee('Komposisi NTE')
            ->assertSee('Rp 584.925.000');
    }
}
