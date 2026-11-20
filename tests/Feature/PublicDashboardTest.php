<?php

namespace Tests\Feature;

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
            ->assertSee('Data Mining: K-Means Clustering Kawasan');
    }

    public function test_public_visitor_can_open_group_information_without_login(): void
    {
        $this->get(route('kelompok.index'))
            ->assertOk()
            ->assertSee('Daftar Kelompok Binaan');
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
