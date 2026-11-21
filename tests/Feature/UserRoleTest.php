<?php

namespace Tests\Feature;

use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_kelompok_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('kelompok.create'))
            ->assertOk();
    }

    public function test_user_cannot_open_kelompok_create_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('kelompok.create'))
            ->assertForbidden();
    }

    public function test_user_cannot_open_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_user_cannot_create_group_data(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->post(route('kelompok.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('kelompoks', 0);
    }

    public function test_user_cannot_update_or_delete_group_data(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Terlindungi',
        ]);

        $this->actingAs($user)
            ->put(route('kelompok.update', $kelompok->id), [
                'kawasan_id' => $kawasan->id,
                'nama_kelompok' => 'Kelompok Diubah',
            ])
            ->assertForbidden();

        $this->delete(route('kelompok.destroy', $kelompok->id))
            ->assertForbidden();

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Terlindungi',
        ]);
    }

    public function test_user_cannot_update_their_profile(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
            ])
            ->assertForbidden();

        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_user_default_role_is_user(): void
    {
        $user = User::factory()->create();

        $this->assertSame('user', $user->role);
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_can_open_public_visualization_and_see_excel_bantuan_link(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('visualisasi'))
            ->assertOk()
            ->assertSee('Isi Data Bantuan')
            ->assertSee('https://bit.ly/Bantuanbksdasulteng2026', false);
    }

    public function test_admin_does_not_see_user_excel_bantuan_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Isi Data Bantuan')
            ->assertDontSee('https://bit.ly/Bantuanbksdasulteng2026', false);
    }

    public function test_excel_bantuan_link_is_not_available_in_sidebar(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('visualisasi'))
            ->assertOk()
            ->assertDontSee('fa-solid fa-file-excel w-5', false);
    }
}
