<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use App\Models\NteSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class AiChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.provider', 'gemini');
        config()->set('services.groq.api_key', '');
    }

    public function test_admin_can_start_a_new_conversation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('ai.chat.conversations.store'))
            ->assertRedirect();

        $conversation = AiConversation::query()
            ->where('user_id', $admin->id)
            ->sole();

        $this->assertSame('Percakapan baru', $conversation->title);
        $this->assertSame(route('ai.chat.show', $conversation), $response->headers->get('Location'));
    }

    public function test_owner_can_delete_a_conversation_and_its_messages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $message = $conversation->messages()->create([
            'role' => AiMessage::ROLE_USER,
            'content' => 'Pertanyaan yang akan dihapus.',
        ]);

        $this->actingAs($admin)
            ->delete(route('ai.chat.conversations.destroy', $conversation), [
                'active_conversation_id' => $conversation->id,
            ])
            ->assertRedirect(route('ai.chat.index'))
            ->assertSessionHas('status', 'Percakapan berhasil dihapus.');

        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('ai_messages', ['id' => $message->id]);

        $this->get(route('ai.chat.index'))
            ->assertOk()
            ->assertSee('Percakapan berhasil dihapus.')
            ->assertSee('Belum ada riwayat percakapan.');
    }

    public function test_deleting_another_conversation_returns_to_the_active_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeConversation = AiConversation::factory()->for($admin)->create();
        $conversationToDelete = AiConversation::factory()->for($admin)->create();

        $this->actingAs($admin)
            ->delete(route('ai.chat.conversations.destroy', $conversationToDelete), [
                'active_conversation_id' => $activeConversation->id,
            ])
            ->assertRedirect(route('ai.chat.show', $activeConversation));

        $this->assertDatabaseHas('ai_conversations', ['id' => $activeConversation->id]);
        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversationToDelete->id]);
    }

    public function test_admin_cannot_delete_another_admins_conversation(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($owner)->create();
        $message = $conversation->messages()->create([
            'role' => AiMessage::ROLE_USER,
            'content' => 'Pesan milik pengguna lain.',
        ]);

        $this->actingAs($otherAdmin)
            ->delete(route('ai.chat.conversations.destroy', $conversation))
            ->assertNotFound();

        $this->assertDatabaseHas('ai_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseHas('ai_messages', ['id' => $message->id]);
    }

    public function test_chat_is_not_blocked_after_eight_requests_per_minute(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.requests_per_minute', 8);
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Jawaban tersedia.']]],
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();

        foreach (range(1, 9) as $requestNumber) {
            $this->actingAs($admin)
                ->postJson(route('ai.chat.messages.store', $conversation), [
                    'message' => 'Pertanyaan '.$requestNumber,
                ])
                ->assertOk()
                ->assertJsonPath('message.content', 'Jawaban tersedia.');
        }

        $this->assertCount(9, Http::recorded());
        $this->assertDatabaseCount('ai_messages', 18);
    }

    public function test_upstream_rate_limit_returns_a_safe_retryable_response(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-primary');
        config()->set('services.gemini.fallback_model', 'gemini-primary');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'error' => [
                    'code' => 429,
                    'status' => 'RESOURCE_EXHAUSTED',
                    'message' => 'secret-test-key; internal request details',
                ],
            ], 429),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $response = $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Tolong ringkas data kelompok.',
            ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Batas penggunaan Gemini tercapai. Tunggu sebentar lalu coba kembali.');

        $this->assertStringNotContainsString('secret-test-key', $response->getContent());
        $this->assertStringNotContainsString('internal request details', $response->getContent());
        $this->assertDatabaseHas('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_USER,
            'content' => 'Tolong ringkas data kelompok.',
        ]);
    }

    public function test_chat_answers_group_count_from_the_database_without_calling_an_ai_provider(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        Kelompok::query()->create(['nama_kelompok' => 'Kelompok Pertama']);
        Kelompok::query()->create(['nama_kelompok' => 'Kelompok Kedua']);

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Berapa jumlah kelompok binaan?',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', 'Jumlah kelompok binaan tercatat 2 kelompok.');

        Http::assertNothingSent();
        $this->assertDatabaseHas('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => 'Jumlah kelompok binaan tercatat 2 kelompok.',
        ]);
    }

    public function test_chat_answers_member_count_from_the_database_without_calling_an_ai_provider(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        Kelompok::query()->create([
            'nama_kelompok' => 'Kelompok Anggota',
            'jumlah_anggota_laki_laki' => 3,
            'jumlah_anggota_perempuan' => 2,
        ]);

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Berapa jumlah anggota perempuan?',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', 'Jumlah anggota perempuan tercatat 2 orang.');

        Http::assertNothingSent();
    }

    public function test_admin_can_chat_with_database_summary_without_sending_contact_details(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Terdapat 1 kelompok binaan.']]],
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        Kelompok::query()->create([
            'nama_kelompok' => 'Kelompok Uji',
            'nama_ketua' => 'Nama Rahasia',
            'no_hp' => '081234567890',
            'status_kelompok' => 'Aktif',
            'jumlah_anggota' => 4,
        ]);

        $this->actingAs($admin)
            ->get(route('ai.chat.index'))
            ->assertOk()
            ->assertSee('Riwayat percakapan');

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Berikan gambaran umum pemberdayaan kelompok binaan.',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', 'Terdapat 1 kelompok binaan.');

        $this->assertDatabaseHas('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_USER,
            'content' => 'Berikan gambaran umum pemberdayaan kelompok binaan.',
        ]);
        $this->assertDatabaseHas('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => 'Terdapat 1 kelompok binaan.',
        ]);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();
            $sentContent = data_get($payload, 'contents.0.parts.0.text', '');

            return $request->hasHeader('x-goog-api-key', 'test-api-key')
                && ! str_contains($request->url(), 'test-api-key')
                && str_contains($sentContent, '"total":1')
                && ! str_contains(json_encode($payload), 'Nama Rahasia')
                && ! str_contains(json_encode($payload), '081234567890');
        });

        $this->actingAs($admin)
            ->get(route('ai.chat.show', $conversation))
            ->assertOk()
            ->assertSee('Terdapat 1 kelompok binaan.')
            ->assertSee('data-send-url="/ai-assistant/chat/conversations/'.$conversation->id.'/messages"', false)
            ->assertSee(route('ai.chat.conversations.destroy', $conversation), false)
            ->assertSee('aria-label="Hapus percakapan: '.$conversation->fresh()->title.'"', false);
    }

    public function test_admin_cannot_read_another_admins_conversation(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($owner)->create();

        $this->actingAs($otherAdmin)
            ->get(route('ai.chat.show', $conversation))
            ->assertNotFound();
    }

    public function test_temporary_gemini_failure_keeps_the_user_message_and_returns_retryable_json(): void
    {
        Http::preventStrayRequests();
        Sleep::fake();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'error' => ['code' => 503, 'status' => 'UNAVAILABLE'],
            ], 503),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Tolong ringkas data kelompok.',
            ])
            ->assertStatus(503)
            ->assertJsonPath(
                'message',
                'Model Gemini utama dan cadangan sedang tidak tersedia atau melewati batas waktu. Coba beberapa saat lagi.',
            );

        $this->assertDatabaseHas('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_USER,
            'content' => 'Tolong ringkas data kelompok.',
        ]);
        $this->assertDatabaseMissing('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_ASSISTANT,
        ]);
    }

    public function test_retrying_a_message_returns_the_saved_answer_without_duplicating_history(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.max_attempts', 1);
        config()->set('services.gemini.fallback_model', 'gemini-2.5-flash');
        $attempts = 0;
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => function () use (&$attempts) {
                $attempts++;

                return $attempts === 1
                    ? Http::response(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503)
                    : Http::response([
                        'candidates' => [[
                            'content' => ['parts' => [['text' => 'Terdapat 1 kelompok binaan.']]],
                        ]],
                    ]);
            },
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $payload = [
            'message' => 'Tolong ringkas data kelompok secara lengkap.',
            'client_message_id' => '51b81620-2280-49e3-9c99-37a8943f0399',
        ];

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), $payload)
            ->assertStatus(503);

        $this->get(route('ai.chat.show', $conversation))
            ->assertOk()
            ->assertSee('data-ai-retry-message="'.$payload['client_message_id'].'"', false);

        $this->postJson(route('ai.chat.messages.store', $conversation), $payload)
            ->assertOk()
            ->assertJsonPath('message.content', 'Terdapat 1 kelompok binaan.');

        $this->postJson(route('ai.chat.messages.store', $conversation), $payload)
            ->assertOk()
            ->assertJsonPath('message.content', 'Terdapat 1 kelompok binaan.');

        $this->assertDatabaseCount('ai_messages', 2);
        $this->assertCount(2, Http::recorded());

        $this->postJson(route('ai.chat.messages.store', $conversation), [
            ...$payload,
            'message' => 'Pertanyaan yang berbeda.',
        ])->assertStatus(409);

        $this->assertDatabaseCount('ai_messages', 2);
    }

    public function test_chat_uses_flash_lite_when_the_primary_model_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Sleep::fake();
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.fallback_model', 'gemini-2.5-flash-lite');
        $primaryRequests = 0;
        $fallbackRequests = 0;
        Http::fake(function (Request $request) use (&$primaryRequests, &$fallbackRequests) {
            if (str_contains($request->url(), '/models/gemini-2.5-flash-lite:generateContent')) {
                $fallbackRequests++;

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => 'Jawaban dari model cadangan.']]],
                    ]],
                ]);
            }

            $primaryRequests++;

            return Http::response(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503);
        });

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Jelaskan ringkasan kelompok binaan terbaru.',
                'client_message_id' => 'e7a2be2e-40be-46d4-b2d8-38bece238738',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', 'Jawaban dari model cadangan.');

        $this->assertSame(2, $primaryRequests);
        $this->assertSame(1, $fallbackRequests);
        $this->assertDatabaseCount('ai_messages', 2);
        Sleep::assertSequence([
            Sleep::for(250)->milliseconds(),
        ]);
    }

    public function test_chat_uses_flash_lite_when_the_primary_model_is_not_found_and_logs_the_attempt(): void
    {
        Http::preventStrayRequests();
        Log::spy();
        config()->set('services.gemini.api_key', 'test-api-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.fallback_model', 'gemini-2.5-flash-lite');
        $primaryRequests = 0;
        $fallbackRequests = 0;
        Http::fake(function (Request $request) use (&$primaryRequests, &$fallbackRequests) {
            if (str_contains($request->url(), '/models/gemini-2.5-flash-lite:generateContent')) {
                $fallbackRequests++;

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => 'Model cadangan berhasil menjawab.']]],
                    ]],
                ]);
            }

            $primaryRequests++;

            return Http::response([
                'error' => [
                    'code' => 404,
                    'status' => 'NOT_FOUND',
                    'message' => 'Model not found.',
                ],
            ], 404);
        });

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Jelaskan ringkasan kelompok binaan terbaru.',
                'client_message_id' => '36e01304-6de2-490e-bd58-f2f8d3f8679b',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', 'Model cadangan berhasil menjawab.');

        $this->assertSame(1, $primaryRequests);
        $this->assertSame(1, $fallbackRequests);
        $this->assertDatabaseCount('ai_messages', 2);
        Log::shouldHaveReceived('error')->withArgs(
            fn (string $message, array $context): bool => $message === 'Gemini API rejected the request.'
                && $context['model'] === 'gemini-2.5-flash'
                && $context['http_status'] === 404
                && $context['error_summary'] === 'The configured model was not found.',
        );
    }

    public function test_chat_sends_only_six_recent_messages_to_gemini(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Siap.']]],
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();

        foreach (range(1, 3) as $turn) {
            $conversation->messages()->create([
                'role' => AiMessage::ROLE_USER,
                'content' => 'Pertanyaan '.$turn,
            ]);
            $conversation->messages()->create([
                'role' => AiMessage::ROLE_ASSISTANT,
                'content' => 'Jawaban '.$turn,
            ]);
        }

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Pertanyaan baru',
                'client_message_id' => 'ec03de29-a728-4d18-83ce-7c1339a4632b',
            ])
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => count($request->data()['contents']) === 6);
    }

    public function test_chat_context_includes_database_village_and_conservation_statistics(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Data tersedia.']]],
                ]],
            ]),
        ]);

        $kawasan = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Uji Chat']);
        $desa = Desa::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Uji Chat',
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Uji Chat',
            'tahun_pembinaan' => 2024,
            'tahun_pembentukan' => 2022,
            'jumlah_anggota_laki_laki' => 3,
            'jumlah_anggota_perempuan' => 2,
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Uji Chat Kedua',
        ]);
        Nte::query()->create([
            'kawasan_id' => $kawasan->id,
            'tahun' => 2025,
            'nte_langsung' => 100,
            'nte_tidak_langsung' => 200,
            'total_nte' => 300,
        ]);
        NteSummary::query()->create([
            'tahun' => 2025,
            'nte_langsung' => 100,
            'nte_tidak_langsung' => 200,
            'total_nte' => 300,
            'nte_langsung_belum_teralokasi' => 0,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Ringkasan pemberdayaan: jumlah desa binaan dan data konservasi berdasarkan bidang dan tahun?',
                'client_message_id' => 'b20a936e-9dc0-4b6a-960f-e7cbfe9cd63a',
            ])
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            $sentContent = data_get($request->data(), 'contents.0.parts.0.text', '');

            return str_contains($sentContent, '"jumlah_desa_dengan_kelompok":1')
                && str_contains($sentContent, '"jumlah_record_kawasan_tahun":1')
                && str_contains($sentContent, '"nte_langsung":100')
                && str_contains($sentContent, '"nte_tidak_langsung":200')
                && str_contains($sentContent, '"total_nte":300')
                && str_contains($sentContent, '"male_members":3')
                && str_contains($sentContent, '"female_members":2');
        });
    }

    public function test_short_follow_up_uses_the_previous_question_to_select_database_context(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'test-api-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Berikut ringkasannya.']]],
                ]],
            ]),
        ]);

        $kawasan = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Lanjutan']);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Lanjutan',
        ]);
        Kelompok::query()->create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Lanjutan Kedua',
        ]);
        $kawasanLain = Kawasan::query()->create(['nama_kawasan' => 'Kawasan Lain']);
        Kelompok::query()->create([
            'kawasan_id' => $kawasanLain->id,
            'nama_kelompok' => 'Kelompok Kawasan Lain',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = AiConversation::factory()->for($admin)->create();
        $conversation->messages()->create([
            'role' => AiMessage::ROLE_USER,
            'content' => 'Kawasan mana yang memiliki kelompok binaan terbanyak?',
        ]);

        $this->actingAs($admin)
            ->postJson(route('ai.chat.messages.store', $conversation), [
                'message' => 'Berapa jumlahnya?',
                'client_message_id' => 'a659733f-b4f6-4e31-af2c-0da22b2523a4',
            ])
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            $sentContent = data_get($request->data(), 'contents.1.parts.0.text', '');

            return str_contains($sentContent, '"kelompok_per_kawasan"')
                && str_contains($sentContent, '"label":"Kawasan Lanjutan","value":2');
        });
    }
}
