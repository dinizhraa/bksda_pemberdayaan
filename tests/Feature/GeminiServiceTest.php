<?php

namespace Tests\Feature;

use App\Services\Ai\GeminiApiException;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gemini_sends_the_api_key_in_a_header_and_requests_json_when_needed(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"ok":true}']]],
                ]],
            ]),
        ]);

        $result = app(GeminiService::class)->generateJson(
            'Balas dengan JSON.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame(['ok' => true], $result);
        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('x-goog-api-key', 'secret-test-key')
                && ! str_contains($request->url(), 'secret-test-key')
                && data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json';
        });
    }

    public function test_gemini_api_key_is_required(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', '');

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected missing API key to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(503, $exception->statusCode);
            $this->assertStringContainsString('API key belum dikonfigurasi', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_gemini_rate_limit_is_reported_as_a_rate_limit_error(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([], 429),
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected Gemini throttling to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(429, $exception->statusCode);
            $this->assertStringContainsString('Batas penggunaan Gemini', $exception->getMessage());
        }

        $this->assertCount(1, Http::recorded());
    }

    public function test_chat_fallback_returns_a_friendly_error_when_both_models_are_rate_limited(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.fallback_model', 'gemini-2.5-flash-lite');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED'],
            ], 429),
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
                allowFallback: true,
            );
            $this->fail('Expected both models to report rate limiting.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(429, $exception->statusCode);
            $this->assertStringContainsString('model utama dan cadangan', $exception->getMessage());
        }

        $this->assertCount(2, Http::recorded());
    }

    public function test_chat_fallback_uses_flash_lite_after_primary_connection_failures(): void
    {
        Http::preventStrayRequests();
        Sleep::fake();
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.fallback_model', 'gemini-2.5-flash-lite');
        $primaryRequests = 0;
        $fallbackRequests = 0;
        Http::fake(function (Request $request) use (&$primaryRequests, &$fallbackRequests) {
            if (str_contains($request->url(), '/models/gemini-2.5-flash-lite:generateContent')) {
                $fallbackRequests++;

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => 'Model cadangan menjawab.']]],
                    ]],
                ]);
            }

            $primaryRequests++;

            return Http::failedConnection();
        });

        $answer = app(GeminiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            allowFallback: true,
        );

        $this->assertSame('Model cadangan menjawab.', $answer);
        $this->assertSame(2, $primaryRequests);
        $this->assertSame(1, $fallbackRequests);
        Sleep::assertSequence([
            Sleep::for(250)->milliseconds(),
        ]);
    }

    public function test_gemini_connection_failure_is_reported_without_exposing_provider_details(): void
    {
        Http::preventStrayRequests();
        Log::spy();
        Sleep::fake();
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-test-primary');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::failedConnection(
                'cURL error 28: Operation timed out',
            ),
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected Gemini connection failure to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(503, $exception->statusCode);
            $this->assertSame(
                'Gemini tidak dapat dijangkau atau melewati batas waktu. Periksa koneksi server lalu coba kembali.',
                $exception->getMessage(),
            );
        }

        $this->assertCount(3, Http::recorded());
        Sleep::assertSequence([
            Sleep::for(250)->milliseconds(),
            Sleep::for(500)->milliseconds(),
        ]);

        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Gemini request exhausted its connection retry budget.'
                    && $context['model'] === 'gemini-test-primary'
                    && $context['exception_type'] === ConnectionException::class
                    && $context['error_type'] === 'timeout'
                    && $context['error_summary'] === 'Gemini request timed out.'
                    && ! str_contains(json_encode($context), 'secret-test-key');
            });
    }

    public function test_temporary_server_errors_are_retried_with_bounded_exponential_backoff(): void
    {
        Http::preventStrayRequests();
        Sleep::fake();
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.max_attempts', 3);
        $attempts = 0;
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => function () use (&$attempts) {
                $attempts++;

                return $attempts < 3
                    ? Http::response(['error' => ['status' => 'UNAVAILABLE']], $attempts === 1 ? 500 : 503)
                    : Http::response([
                        'candidates' => [[
                            'content' => ['parts' => [['text' => 'Gemini kembali tersedia.']]],
                        ]],
                    ]);
            },
        ]);

        $answer = app(GeminiService::class)->generateText(
            'Instruksi.',
            [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
        );

        $this->assertSame('Gemini kembali tersedia.', $answer);
        $this->assertCount(3, Http::recorded());
        Sleep::assertSequence([
            Sleep::for(250)->milliseconds(),
            Sleep::for(500)->milliseconds(),
        ]);
    }

    public function test_retry_budget_stops_after_three_unavailable_responses(): void
    {
        Http::preventStrayRequests();
        Sleep::fake();
        config()->set('services.gemini.api_key', 'secret-test-key');
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'error' => [
                    'code' => 503,
                    'status' => 'UNAVAILABLE',
                    'message' => 'Model sedang sibuk.',
                ],
            ], 503),
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected exhausted retries to return a service error.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(503, $exception->statusCode);
            $this->assertStringContainsString('sedang padat', $exception->getMessage());
        }

        $this->assertCount(3, Http::recorded());
        Sleep::assertSequence([
            Sleep::for(250)->milliseconds(),
            Sleep::for(500)->milliseconds(),
        ]);
    }

    public function test_invalid_api_key_and_model_errors_have_specific_user_messages(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        $responses = [
            Http::response([
                'error' => [
                    'code' => 400,
                    'status' => 'INVALID_ARGUMENT',
                    'message' => 'API key not valid. Please pass a valid API key.',
                ],
            ], 400),
            Http::response([
                'error' => [
                    'code' => 404,
                    'status' => 'NOT_FOUND',
                    'message' => 'Model not found.',
                ],
            ], 404),
        ];
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => function () use (&$responses) {
                return array_shift($responses);
            },
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected an invalid API key to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(502, $exception->statusCode);
            $this->assertStringContainsString('API key Gemini ditolak', $exception->getMessage());
        }

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected a missing model to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(502, $exception->statusCode);
            $this->assertStringContainsString('GEMINI_MODEL', $exception->getMessage());
        }
    }

    public function test_provider_error_details_and_api_key_are_not_written_to_logs(): void
    {
        Http::preventStrayRequests();
        Log::spy();
        $apiKey = 'secret-test-key';
        config()->set('services.gemini.api_key', $apiKey);
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'error' => [
                    'code' => 400,
                    'status' => 'INVALID_ARGUMENT',
                    'message' => "API key not valid: {$apiKey}",
                ],
            ], 400),
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected an invalid API key to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(502, $exception->statusCode);
        }

        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context) use ($apiKey): bool {
                return ! str_contains($message, $apiKey)
                    && ! str_contains(json_encode($context), $apiKey)
                    && ! array_key_exists('response', $context)
                    && $context['http_status'] === 400
                    && $context['provider_message_summary'] === 'API key not valid: [redacted]';
            });
    }

    public function test_unauthorized_and_forbidden_errors_are_mapped_to_safe_messages(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        $responses = [
            Http::response([
                'error' => ['code' => 401, 'status' => 'UNAUTHENTICATED'],
            ], 401),
            Http::response([
                'error' => ['code' => 403, 'status' => 'PERMISSION_DENIED'],
            ], 403),
        ];
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => function () use (&$responses) {
                return array_shift($responses);
            },
        ]);
        foreach ([
            401 => 'API key Gemini tidak valid',
            403 => 'Akses ke Gemini ditolak',
        ] as $status => $expectedMessage) {
            try {
                app(GeminiService::class)->generateText(
                    'Instruksi.',
                    [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
                );
                $this->fail('Expected Gemini authorization failure to be reported.');
            } catch (GeminiApiException $exception) {
                $this->assertSame(502, $exception->statusCode);
                $this->assertStringContainsString($expectedMessage, $exception->getMessage());
            }
        }
    }

    public function test_empty_and_malformed_gemini_responses_are_reported(): void
    {
        Http::preventStrayRequests();
        config()->set('services.gemini.api_key', 'secret-test-key');
        $responses = [
            Http::response(['candidates' => []]),
            Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'bukan json']]],
                ]],
            ]),
        ];
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => function () use (&$responses) {
                return array_shift($responses);
            },
        ]);

        try {
            app(GeminiService::class)->generateText(
                'Instruksi.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected an empty response to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(502, $exception->statusCode);
            $this->assertStringContainsString('tidak mengembalikan jawaban', $exception->getMessage());
        }

        try {
            app(GeminiService::class)->generateJson(
                'Balas dengan JSON.',
                [['role' => 'user', 'parts' => [['text' => 'Uji']]]],
            );
            $this->fail('Expected malformed JSON to be reported.');
        } catch (GeminiApiException $exception) {
            $this->assertSame(502, $exception->statusCode);
            $this->assertStringContainsString('Format jawaban Gemini tidak valid', $exception->getMessage());
        }
    }
}
