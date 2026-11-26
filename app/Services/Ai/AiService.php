<?php

namespace App\Services\Ai;

use App\Services\GeminiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     */
    public function __construct(private readonly GeminiService $gemini) {}

    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     */
    public function generateText(
        string $systemInstruction,
        array $contents,
        bool $structured = false,
        bool $allowFallback = false,
    ): string {
        $provider = $this->primaryProvider();

        if ($provider === 'gemini') {
            return $this->gemini->generateText($systemInstruction, $contents, $structured, $allowFallback);
        }

        try {
            return $this->requestGroq($systemInstruction, $contents, $structured);
        } catch (GeminiApiException $exception) {
            return $this->fallbackToGemini($systemInstruction, $contents, $structured, $exception);
        }
    }

    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     * @return array<string, mixed>
     */
    public function generateJson(string $systemInstruction, array $contents): array
    {
        if ($this->primaryProvider() === 'gemini') {
            return $this->gemini->generateJson($systemInstruction, $contents);
        }

        try {
            return $this->decodeJson($this->requestGroq($systemInstruction, $contents, structured: true));
        } catch (GeminiApiException $exception) {
            $text = $this->fallbackToGemini($systemInstruction, $contents, true, $exception);

            return $this->decodeJson($text);
        }
    }

    /**
     * @return 'groq'|'gemini'
     */
    private function primaryProvider(): string
    {
        $provider = config('services.ai.provider', 'groq');
        $groqApiKey = config('services.groq.api_key');
        $hasGroqApiKey = is_string($groqApiKey) && trim($groqApiKey) !== '';

        if ($provider === 'groq' || $hasGroqApiKey) {
            return 'groq';
        }

        if ($provider === 'gemini') {
            return 'gemini';
        }

        Log::error('AI provider configuration is invalid.');

        throw new GeminiApiException('Penyedia AI tidak valid. Periksa konfigurasi AI_PROVIDER.', 503);
    }

    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     */
    private function fallbackToGemini(
        string $systemInstruction,
        array $contents,
        bool $structured,
        GeminiApiException $exception,
    ): string {
        $geminiApiKey = config('services.gemini.api_key');

        if (! is_string($geminiApiKey) || trim($geminiApiKey) === '') {
            throw $exception;
        }

        Log::warning('Groq request failed; falling back to Gemini.', [
            'http_status' => $exception->providerHttpStatus ?? $exception->statusCode,
        ]);

        return $this->gemini->generateText(
            $systemInstruction,
            $contents,
            $structured,
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('AI provider returned invalid JSON.', [
                'json_error' => json_last_error_msg(),
            ]);

            throw new GeminiApiException(
                'Format jawaban AI tidak valid. Silakan coba kembali.',
                502,
            );
        }

        return $decoded;
    }

    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     */
    private function requestGroq(string $systemInstruction, array $contents, bool $structured): string
    {
        $apiKey = config('services.groq.api_key');
        $model = config('services.groq.model');
        $baseUrl = config('services.groq.base_url');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new GeminiApiException('Groq API key belum dikonfigurasi.', 503);
        }

        if (! is_string($model) || trim($model) === '') {
            throw new GeminiApiException('Model Groq belum dikonfigurasi.', 503);
        }

        $baseUrl = is_string($baseUrl) ? trim($baseUrl) : '';
        $baseUrlParts = parse_url($baseUrl);

        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! is_array($baseUrlParts)
            || ($baseUrlParts['scheme'] ?? null) !== 'https'
            || ! isset($baseUrlParts['host'])
            || isset($baseUrlParts['user'])
            || isset($baseUrlParts['pass'])
            || isset($baseUrlParts['query'])
            || isset($baseUrlParts['fragment'])) {
            throw new GeminiApiException('URL layanan Groq belum dikonfigurasi dengan benar.', 503);
        }

        $apiKey = trim($apiKey);
        $model = trim($model);
        $timeout = max(1, min(120, (int) config('services.groq.timeout', 30)));
        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];

        foreach ($contents as $content) {
            $messages[] = [
                'role' => in_array($content['role'], ['model', 'assistant'], true) ? 'assistant' : 'user',
                'content' => implode("\n", array_column($content['parts'], 'text')),
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.2,
        ];

        if ($structured) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->connectTimeout(min(5, $timeout))
                ->timeout($timeout)
                ->post($baseUrl, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Groq request encountered a connection failure.', [
                'model' => $model,
                'exception_type' => $exception::class,
            ]);

            throw new GeminiApiException(
                'Groq tidak dapat dijangkau atau melewati batas waktu.',
                503,
                $exception,
            );
        }

        if (! $response->successful()) {
            $this->throwForGroqResponse($response, $model);
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            Log::warning('Groq returned an empty or unusable response.', [
                'model' => $model,
                'http_status' => $response->status(),
            ]);

            throw new GeminiApiException(
                'Groq tidak mengembalikan jawaban yang dapat digunakan.',
                502,
                providerHttpStatus: $response->status(),
            );
        }

        return trim($text);
    }

    private function throwForGroqResponse(Response $response, string $model): never
    {
        $status = $response->status();

        Log::warning('Groq API rejected the request.', [
            'model' => $model,
            'http_status' => $status,
        ]);

        throw new GeminiApiException(
            'Groq gagal memproses permintaan.',
            match ($status) {
                429 => 429,
                default => $status >= 500 ? 503 : 502,
            },
            providerHttpStatus: $status,
        );
    }
}
