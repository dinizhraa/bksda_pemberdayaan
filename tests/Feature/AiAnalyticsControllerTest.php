<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use App\Services\Ai\AnalyticsService;
use App\Services\Ai\ChatContextService;
use App\Services\Ai\DataSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.provider', 'gemini');
        config()->set('services.groq.api_key', '');
    }

    public function test_analytics_filter_computes_database_totals_and_returns_an_ai_insight(): void
    {
        Http::preventStrayRequests();
        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.groq.base_url', 'https://api.groq.com/openai/v1/chat/completions');
        config()->set('services.groq.model', 'llama-test');
        config()->set('services.gemini.api_key', '');
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Satu kelompok tercatat aktif.'],
                ]],
            ]),
        ]);

        $kawasan = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Uji']);
        $desa = Desa::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Uji',
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Aktif 2024',
            'tahun_pembinaan' => 2024,
            'status_kelompok' => 'Aktif',
            'jumlah_anggota_laki_laki' => 3,
            'jumlah_anggota_perempuan' => 2,
            'jumlah_bantuan' => 1250000,
            'tahun_monitoring' => 2025,
            'nama_ketua' => 'Ketua Rahasia',
            'no_hp' => '081234567890',
        ]);
        Kelompok::query()->create([
            'nama_kelompok' => 'Tidak Aktif 2023',
            'tahun_pembinaan' => 2023,
            'status_kelompok' => 'Tidak Aktif',
            'jumlah_bantuan' => 500000,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('ai.analytics.index', ['year' => 2024]))
            ->assertOk()
            ->assertSee('Jumlah Kelompok')
            ->assertSee('2025:')
            ->assertSee('1');

        $this->postJson(route('ai.analytics.insight'), [
            'year' => 2024,
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'status' => 'Aktif',
        ])
            ->assertOk()
            ->assertJsonPath('insight', 'Satu kelompok tercatat aktif.');

        Http::assertSent(function (Request $request): bool {
            $sentContent = data_get($request->data(), 'messages.1.content', '');
            $payload = json_encode($request->data());

            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer groq-test-key')
                && data_get($request->data(), 'model') === 'llama-test'
                && str_contains($sentContent, '"jumlah_kelompok":1')
                && str_contains($sentContent, '"aktif":1')
                && str_contains($sentContent, '"laki_laki":3')
                && str_contains($sentContent, '"perempuan":2')
                && str_contains($sentContent, '"total_bantuan":1250000')
                && str_contains($sentContent, '"sebaran_per_kawasan":[{"kawasan":"Kawasan Uji","jumlah_kelompok":1}]')
                && ! str_contains($payload, 'Ketua Rahasia')
                && ! str_contains($payload, '081234567890');
        });
    }

    public function test_insight_cache_is_reused_per_filter_combination_for_thirty_minutes(): void
    {
        Http::preventStrayRequests();
        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.groq.base_url', 'https://api.groq.com/openai/v1/chat/completions');
        config()->set('services.gemini.api_key', '');
        $responseNumber = 0;
        Http::fake(function (Request $request) use (&$responseNumber) {
            $responseNumber++;

            return Http::response([
                'choices' => [[
                    'message' => ['content' => 'Insight '.$responseNumber],
                ]],
            ]);
        });

        $kawasan = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Cache A']);
        $kawasanLain = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Cache B']);
        $desa = Desa::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Cache A',
        ]);
        $desaLain = Desa::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Cache B',
        ]);
        $desaKawasanLain = Desa::query()->create([
            'kawasan_id' => $kawasanLain->id,
            'nama_desa' => 'Desa Cache C',
        ]);

        foreach ([
            [$kawasan->id, $desa->id, 2024, 'Aktif'],
            [$kawasan->id, $desa->id, 2024, 'Tidak Aktif'],
            [$kawasan->id, $desa->id, 2023, 'Aktif'],
            [$kawasan->id, $desaLain->id, 2024, 'Aktif'],
            [$kawasanLain->id, $desaKawasanLain->id, 2024, 'Aktif'],
        ] as [$kawasanId, $desaId, $year, $status]) {
            Kelompok::query()->create([
                'kawasan_id' => $kawasanId,
                'desa_id' => $desaId,
                'nama_kelompok' => 'Kelompok Cache',
                'tahun_pembinaan' => $year,
                'status_kelompok' => $status,
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $baseFilters = [
            'year' => 2024,
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'status' => 'Aktif',
        ];
        $route = route('ai.analytics.insight');

        $this->postJson($route, $baseFilters)
            ->assertOk()
            ->assertJsonPath('insight', 'Insight 1');
        $this->postJson($route, $baseFilters)
            ->assertOk()
            ->assertJsonPath('insight', 'Insight 1');

        $filterVariations = [
            [...$baseFilters, 'year' => 2023],
            [...$baseFilters, 'kawasan_id' => $kawasanLain->id, 'desa_id' => $desaKawasanLain->id],
            [...$baseFilters, 'desa_id' => $desaLain->id],
            [...$baseFilters, 'status' => 'Tidak Aktif'],
        ];

        foreach ($filterVariations as $index => $filters) {
            $this->postJson($route, $filters)
                ->assertOk()
                ->assertJsonPath('insight', 'Insight '.($index + 2));
        }

        $this->travel(29)->minutes();
        $this->postJson($route, $baseFilters)
            ->assertOk()
            ->assertJsonPath('insight', 'Insight 1');

        $this->travel(2)->minutes();
        $this->postJson($route, $baseFilters)
            ->assertOk()
            ->assertJsonPath('insight', 'Insight 6');

        $this->assertCount(6, Http::recorded());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_insight_sends_only_filtered_aggregate_data_without_group_or_contact_details(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Insight agregat tersedia.']]],
                ]],
            ]),
        ]);

        $kawasan = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Agregat']);
        $desa = Desa::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Tidak Dikirim',
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Nama Kelompok Rahasia',
            'nama_ketua' => 'Nama Ketua Rahasia',
            'no_hp' => '081234567891',
            'tahun_pembinaan' => 2024,
            'tahun_monitoring' => 2025,
            'status_kelompok' => 'Aktif',
            'jumlah_anggota_laki_laki' => 3,
            'jumlah_anggota_perempuan' => 2,
            'jumlah_bantuan' => 1250000,
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Tanpa Status',
            'nama_ketua' => 'Ketua Tanpa Status',
            'no_hp' => '081234567892',
            'tahun_pembinaan' => 2024,
            'tahun_monitoring' => null,
            'status_kelompok' => null,
            'jumlah_anggota_laki_laki' => 1,
            'jumlah_anggota_perempuan' => 4,
            'jumlah_bantuan' => 500000,
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Tahun Lain',
            'tahun_pembinaan' => 2023,
            'status_kelompok' => 'Tidak Aktif',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->postJson(route('ai.analytics.insight'), [
                'year' => 2024,
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
            ])
            ->assertOk()
            ->assertJsonPath('insight', 'Insight agregat tersedia.');

        Http::assertSent(function (Request $request): bool {
            $sentContent = data_get($request->data(), 'contents.0.parts.0.text', '');
            $payload = json_encode($request->data());

            return str_contains($sentContent, '"jumlah_kelompok":2')
                && str_contains($sentContent, '"status_kelompok":{"aktif":1,"tidak_aktif":0}')
                && str_contains($sentContent, '"anggota_terdata":{"laki_laki":4,"perempuan":6}')
                && str_contains($sentContent, '"total_bantuan":1750000')
                && str_contains($sentContent, '"sebaran_per_kawasan":[{"kawasan":"Kawasan Agregat","jumlah_kelompok":2}]')
                && ! str_contains($sentContent, 'monitoring')
                && ! str_contains($sentContent, 'groups_by_village')
                && ! str_contains($payload, 'Nama Kelompok Rahasia')
                && ! str_contains($payload, 'Nama Ketua Rahasia')
                && ! str_contains($payload, 'Nama Ketua Tanpa Status')
                && ! str_contains($payload, '081234567891')
                && ! str_contains($payload, '081234567892')
                && ! str_contains($payload, 'Desa Tidak Dikirim');
        });
    }

    public function test_insight_uses_the_fallback_model_when_the_primary_model_returns_404(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.model', 'gemini-primary');
        config()->set('services.gemini.fallback_model', 'gemini-fallback');
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/models/gemini-primary:generateContent')) {
                return Http::response([
                    'error' => ['code' => 404, 'status' => 'NOT_FOUND'],
                ], 404);
            }

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Insight dari model cadangan.']]],
                ]],
            ]);
        });
        Kelompok::query()->create(['nama_kelompok' => 'Kelompok Uji']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('ai.analytics.insight'))
            ->assertOk()
            ->assertJsonPath('insight', 'Insight dari model cadangan.');

        $this->assertCount(2, Http::recorded());
        $this->assertStringContainsString('/models/gemini-primary:generateContent', Http::recorded()[0][0]->url());
        $this->assertStringContainsString('/models/gemini-fallback:generateContent', Http::recorded()[1][0]->url());
    }

    public function test_insight_is_not_blocked_after_eight_requests_per_minute(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.requests_per_minute', 8);
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Insight tersedia.']]],
                ]],
            ]),
        ]);
        Kelompok::query()->create(['nama_kelompok' => 'Kelompok Uji']);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 9) as $requestNumber) {
            $this->actingAs($admin)
                ->postJson(route('ai.analytics.insight'))
                ->assertOk()
                ->assertJsonPath('insight', 'Insight tersedia.');
        }

        $this->assertCount(1, Http::recorded());
    }

    public function test_insight_includes_every_area_in_the_aggregate_distribution(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Sebaran kawasan tersedia.']]],
                ]],
            ]),
        ]);

        foreach (range(1, 13) as $number) {
            $kawasan = Kawasan::query()->create(['nama_kawasan' => 'Kawasan '.$number]);
            Kelompok::query()->create([
                'kawasan_id' => $kawasan->id,
                'nama_kelompok' => 'Kelompok '.$number,
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->postJson(route('ai.analytics.insight'))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            $sentContent = data_get($request->data(), 'contents.0.parts.0.text', '');
            $json = explode("\n", $sentContent, 2)[1] ?? '';
            $data = json_decode($json, true);

            return count($data['sebaran_per_kawasan'] ?? []) === 13;
        });
    }

    public function test_report_records_are_flagged_truncated_only_when_more_than_thirty_exist(): void
    {
        foreach (range(1, 30) as $number) {
            Kelompok::query()->create(['nama_kelompok' => 'Kelompok '.$number]);
        }

        $analytics = app(AnalyticsService::class);
        $sanitizer = app(DataSanitizer::class);
        $thirtyRecords = $analytics->reportData([], 'groups', $sanitizer);

        $this->assertCount(30, $thirtyRecords['records']);
        $this->assertFalse($thirtyRecords['records_truncated']);

        Kelompok::query()->create(['nama_kelompok' => 'Kelompok 31']);
        $thirtyOneRecords = $analytics->reportData([], 'groups', $sanitizer);

        $this->assertCount(30, $thirtyOneRecords['records']);
        $this->assertTrue($thirtyOneRecords['records_truncated']);

        $chatContext = app(ChatContextService::class)->forQuestion('Kelompok mana belum dimonitoring?');
        $this->assertCount(30, $chatContext['kelompok_belum_dimonitoring']['rows']);
        $this->assertTrue($chatContext['kelompok_belum_dimonitoring']['truncated']);
    }

    public function test_insight_with_no_matching_database_rows_does_not_call_gemini(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('ai.analytics.insight'), ['year' => 2020])
            ->assertOk()
            ->assertJsonPath(
                'insight',
                'Tidak ada data kelompok yang sesuai dengan filter. Ubah filter atau tambahkan data sebelum membuat insight.',
            );

        Http::assertNothingSent();
    }

    public function test_provider_404_429_and_503_errors_return_and_cache_a_php_generated_insight(): void
    {
        Http::preventStrayRequests();
        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.groq.base_url', 'https://api.groq.com/openai/v1/chat/completions');
        config()->set('services.groq.model', 'llama-test');
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.model', 'gemini-primary');
        config()->set('services.gemini.fallback_model', 'gemini-fallback');
        config()->set('services.gemini.max_attempts', 1);
        $upstreamStatus = 404;
        Http::fake(function (Request $request) use (&$upstreamStatus) {
            return Http::response([
                'error' => [
                    'code' => $upstreamStatus,
                    'status' => 'UNAVAILABLE',
                ],
            ], $upstreamStatus);
        });

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        foreach ([404 => 2024, 429 => 2025, 503 => 2026] as $status => $year) {
            $upstreamStatus = $status;
            Kelompok::query()->create([
                'nama_kelompok' => 'Kelompok '.$year,
                'tahun_pembinaan' => $year,
                'status_kelompok' => 'Aktif',
                'jumlah_anggota_laki_laki' => 2,
                'jumlah_anggota_perempuan' => 3,
                'jumlah_bantuan' => 1000000,
            ]);

            $filters = ['year' => $year];
            $response = $this->postJson(route('ai.analytics.insight'), $filters)
                ->assertOk()
                ->assertJsonStructure(['insight']);

            $this->assertStringContainsString('1 kelompok', $response->json('insight'));
            $this->assertStringContainsString('2 laki-laki dan 3 perempuan', $response->json('insight'));
            $this->assertStringContainsString('Rp1.000.000', $response->json('insight'));
            $this->assertStringContainsString('Rekomendasi:', $response->json('insight'));

            $this->postJson(route('ai.analytics.insight'), $filters)
                ->assertOk()
                ->assertJsonPath('insight', $response->json('insight'));
        }

        $this->assertCount(9, Http::recorded());
    }
}
