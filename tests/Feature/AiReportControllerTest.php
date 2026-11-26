<?php

namespace Tests\Feature;

use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.provider', 'gemini');
        config()->set('services.groq.api_key', '');
    }

    public function test_report_index_renders_filters_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('ai.reports.index'))
            ->assertOk()
            ->assertSee('Jenis Laporan')
            ->assertSee('Kelompok');
    }

    public function test_monev_report_without_notes_displays_notice_without_calling_gemini(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);
        Kelompok::query()->create(['nama_kelompok' => 'Kelompok Tanpa Catatan']);

        $this->actingAs($admin)
            ->post(route('ai.reports.preview'), ['type' => 'monev'])
            ->assertOk()
            ->assertSee('Data catatan Monev belum tersedia')
            ->assertSee('Kelompok Tanpa Catatan');

        Http::assertNothingSent();
    }

    public function test_admin_can_preview_and_export_a_database_report_as_word(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'pendahuluan' => 'Laporan ini merangkum data kelompok binaan.',
                            'gambaran_umum' => 'Terdapat satu kelompok dalam cakupan laporan.',
                            'hasil_analisis' => 'Data bantuan dan anggota dihitung dari database.',
                            'kesimpulan' => 'Informasi kelompok tersedia untuk ditinjau.',
                            'rekomendasi' => ['Perbarui data kelompok secara berkala.'],
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ]]],
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        Kelompok::query()->create([
            'nama_kelompok' => 'Kelompok Laporan',
            'tahun_pembinaan' => 2024,
            'jumlah_anggota_laki_laki' => 2,
            'jumlah_anggota_perempuan' => 3,
            'jumlah_bantuan' => 1000000,
        ]);

        $preview = $this->actingAs($admin)
            ->post(route('ai.reports.preview'), [
                'type' => 'groups',
                'period_start' => 2024,
                'period_end' => 2024,
            ])
            ->assertOk()
            ->assertSee('Laporan Kelompok Binaan')
            ->assertSee('Kelompok Laporan')
            ->assertSee('Perbarui data kelompok secara berkala.');

        $reportId = $preview->viewData('reportId');
        $this->assertIsString($reportId);

        $this->get(route('ai.reports.word', $reportId))
            ->assertOk()
            ->assertHeader('content-type', 'application/msword; charset=UTF-8');
    }

    public function test_report_period_must_be_in_ascending_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('ai.reports.index'))
            ->post(route('ai.reports.preview'), [
                'type' => 'groups',
                'period_start' => 2025,
                'period_end' => 2024,
            ])
            ->assertRedirect(route('ai.reports.index'))
            ->assertSessionHasErrors([
                'period_end' => 'Akhir periode harus sama atau lebih besar dari awal periode.',
            ]);
    }
}
