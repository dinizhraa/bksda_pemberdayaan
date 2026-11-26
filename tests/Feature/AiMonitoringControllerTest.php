<?php

namespace Tests\Feature;

use App\Models\AiGroupEvaluation;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiMonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.provider', 'gemini');
        config()->set('services.groq.api_key', '');
    }

    public function test_group_without_monitoring_data_shows_notice_and_does_not_call_gemini(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);
        $kelompok = Kelompok::query()->create(['nama_kelompok' => 'Kelompok Belum Dimonitor']);

        $this->actingAs($admin)
            ->get(route('ai.monev.index'))
            ->assertOk()
            ->assertSee('Kelompok Belum Dimonitor');

        $this->actingAs($admin)
            ->get(route('ai.monev.show', $kelompok))
            ->assertOk()
            ->assertSee('Data monitoring dan catatan Monev belum tersedia');

        $this->actingAs($admin)
            ->post(route('ai.monev.analyze', $kelompok))
            ->assertSessionHas('monev_notice');

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_group_evaluations', 0);
    }

    public function test_admin_can_create_a_monev_evaluation_without_transmitting_contact_details(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'priority' => 'sedang',
                            'summary' => 'Catatan kebutuhan tercatat dan perlu ditindaklanjuti.',
                            'findings' => ['Catatan menyebut kebutuhan sarana.'],
                            'recommendations' => ['Konfirmasi kebutuhan melalui pendampingan lapangan.'],
                            'rationale' => 'Prioritas berdasarkan kebutuhan yang disebut dalam catatan.',
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $kelompok = Kelompok::query()->create([
            'nama_kelompok' => 'Kelompok Monev',
            'nama_ketua' => 'Nama Rahasia',
            'no_hp' => '081234567890',
            'tahun_monitoring' => 2025,
            'catatan_kebutuhan_hasil_monev' => 'Membutuhkan sarana. Kontak: 081234567890.',
        ]);

        $this->actingAs($admin)
            ->post(route('ai.monev.analyze', $kelompok))
            ->assertRedirect(route('ai.monev.show', $kelompok))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ai_group_evaluations', [
            'kelompok_id' => $kelompok->id,
            'user_id' => $admin->id,
            'priority' => 'sedang',
        ]);
        $this->assertSame(1, AiGroupEvaluation::query()->count());

        Http::assertSent(function (Request $request): bool {
            return ! str_contains($request->body(), 'Nama Rahasia')
                && ! str_contains($request->body(), '081234567890')
                && str_contains($request->body(), '[nomor telepon disamarkan]');
        });
    }
}
