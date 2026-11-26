<?php

namespace Tests\Feature;

use App\Services\Ai\AiService;
use App\Services\Ai\GeminiApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.base_url', 'https://api.groq.com/openai/v1/chat/completions');
    }

    public function test_groq_uses_openai_chat_completions_format_as_the_primary_provider(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.groq.model', 'llama-test');
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Jawaban Groq.'],
                ]],
            ]),
        ]);

        $answer = app(AiService::class)->generateText(
            'Instruksi sistem.',
            [
                ['role' => 'user', 'parts' => [['text' => 'Pertanyaan']]],
                ['role' => 'model', 'parts' => [['text' => 'Jawaban sebelumnya']]],
            ],
        );

        $this->assertSame('Jawaban Groq.', $answer);
        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer groq-test-key')
                && $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && data_get($request->data(), 'model') === 'llama-test'
                && data_get($request->data(), 'messages.0.role') === 'system'
                && data_get($request->data(), 'messages.1.role') === 'user'
                && data_get($request->data(), 'messages.2.role') === 'assistant'
                && data_get($request->data(), 'messages.2.content') === 'Jawaban sebelumnya';
        });

        $this->assertCount(1, Http::recorded());
    }

    public function test_configured_groq_key_takes_precedence_over_gemini_provider(): void
    {
        Http::preventStrayRequests();
        config()->set('services.ai.provider', 'gemini');
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.gemini.api_key', '');
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Jawaban Groq.'],
                ]],
            ]),
        ]);

        $answer = app(AiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame('Jawaban Groq.', $answer);
        $this->assertCount(1, Http::recorded());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_groq_failure_does_not_require_optional_gemini_key(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.gemini.api_key', '');
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Private provider details'],
            ], 429),
        ]);

        try {
            app(AiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected the Groq error to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(429, $exception->statusCode);
            $this->assertSame('Groq gagal memproses permintaan.', $exception->getMessage());
        }

        $this->assertCount(1, Http::recorded());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_gemini_is_used_when_groq_api_key_is_missing(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', '');
        config()->set('services.gemini.api_key', 'gemini-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Jawaban Gemini.']]],
                ]],
            ]),
        ]);

        $answer = app(AiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame('Jawaban Gemini.', $answer);
        $this->assertCount(1, Http::recorded());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'generativelanguage.googleapis.com'));
    }

    public function test_structured_groq_requests_enable_json_response_format(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => '{"ok":true}'],
                ]],
            ]),
        ]);

        $result = app(AiService::class)->generateJson(
            'Balas dengan JSON.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame(['ok' => true], $result);
        Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'response_format.type') === 'json_object');
    }

    public function test_invalid_groq_json_falls_back_to_gemini(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.gemini.api_key', 'gemini-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');
        config()->set('services.gemini.fallback_model', 'gemini-test-model');
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.groq.com')) {
                return Http::response([
                    'choices' => [[
                        'message' => ['content' => 'not-json'],
                    ]],
                ]);
            }

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"ok":true}']]],
                ]],
            ]);
        });

        $result = app(AiService::class)->generateJson(
            'Balas dengan JSON.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame(['ok' => true], $result);
        $this->assertCount(2, Http::recorded());
        $this->assertStringContainsString('generativelanguage.googleapis.com', Http::recorded()[1][0]->url());
    }

    public function test_groq_rate_limit_falls_back_to_gemini(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.gemini.api_key', 'gemini-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.groq.com')) {
                return Http::response(['error' => ['message' => 'Private provider details']], 429);
            }

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Jawaban Gemini cadangan.']]],
                ]],
            ]);
        });

        $answer = app(AiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame('Jawaban Gemini cadangan.', $answer);
        $this->assertCount(2, Http::recorded());
        $this->assertStringContainsString('models/gemini-test-model:generateContent', Http::recorded()[1][0]->url());
    }

    public function test_groq_server_failure_falls_back_to_gemini_fallback_model(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.gemini.api_key', 'gemini-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');
        config()->set('services.gemini.fallback_model', 'gemini-fallback-model');
        config()->set('services.gemini.max_attempts', 1);
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.groq.com')) {
                return Http::response(['error' => ['message' => 'Internal provider detail']], 500);
            }

            if (str_contains($request->url(), '/models/gemini-test-model:generateContent')) {
                return Http::response(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503);
            }

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Jawaban model Gemini cadangan.']]],
                ]],
            ]);
        });

        $answer = app(AiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame('Jawaban model Gemini cadangan.', $answer);
        $this->assertCount(3, Http::recorded());
        $this->assertStringContainsString('/models/gemini-fallback-model:generateContent', Http::recorded()[2][0]->url());
    }

    public function test_groq_timeout_falls_back_to_gemini(): void
    {
        Http::preventStrayRequests();
        config()->set('services.groq.api_key', 'groq-test-key');
        config()->set('services.gemini.api_key', 'gemini-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.groq.com')) {
                return Http::failedConnection('The request timed out.');
            }

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Jawaban setelah timeout.']]],
                ]],
            ]);
        });

        $answer = app(AiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame('Jawaban setelah timeout.', $answer);
        $this->assertCount(2, Http::recorded());
    }
}
