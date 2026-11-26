<?php

namespace App\Services;

use App\Services\Ai\GeminiApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

class GeminiService
{
    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     */
    public function generateText(
        string $systemInstruction,
        array $contents,
        bool $structured = false,
        bool $allowFallback = false,
    ): string {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');
        $fallbackModel = config('services.gemini.fallback_model');
        $baseUrl = config('services.gemini.base_url');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            Log::error('Gemini API key is not configured.');

            throw new GeminiApiException('Gemini API key belum dikonfigurasi pada server.', 503);
        }

        if (! is_string($model) || trim($model) === '') {
            Log::error('Gemini model is not configured.');

            throw new GeminiApiException('Model Gemini belum dikonfigurasi pada server.', 503);
        }

        $baseUrl = is_string($baseUrl) ? trim($baseUrl) : $baseUrl;
        $baseUrlParts = is_string($baseUrl) ? parse_url($baseUrl) : false;

        if (! is_string($baseUrl)
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! is_array($baseUrlParts)
            || ($baseUrlParts['scheme'] ?? null) !== 'https'
            || ! isset($baseUrlParts['host'])
            || isset($baseUrlParts['user'])
            || isset($baseUrlParts['pass'])
            || isset($baseUrlParts['query'])
            || isset($baseUrlParts['fragment'])) {
            Log::error('Gemini base URL is invalid.');

            throw new GeminiApiException('URL layanan Gemini belum dikonfigurasi dengan benar.', 503);
        }

        $apiKey = trim($apiKey);
        $model = trim($model);
        $fallbackModel = is_string($fallbackModel) ? trim($fallbackModel) : '';
        $fallbackEnabled = $allowFallback && $fallbackModel !== '' && $fallbackModel !== $model;
        $connectTimeout = max(1, min(120, (int) config('services.gemini.connect_timeout', 5)));
        $timeout = max($connectTimeout, min(120, (int) config('services.gemini.timeout', 30)));
        $totalTimeout = min(
            120,
            max($timeout, (int) config('services.gemini.total_timeout', min(120, $timeout * 2))),
        );
        $maxAttempts = max(1, min(3, (int) config('services.gemini.max_attempts', 3)));
        $retryBaseDelay = max(50, min(1000, (int) config('services.gemini.retry_base_delay_ms', 250)));
        $retryMaxDelay = max($retryBaseDelay, min(2000, (int) config('services.gemini.retry_max_delay_ms', 1000)));
        $generationConfig = [
            'temperature' => 0.2,
            'maxOutputTokens' => max(128, (int) config('services.gemini.max_output_tokens', 2048)),
        ];

        if ($structured) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $deadline = hrtime(true) + ($totalTimeout * 1_000_000_000);
        $fallbackReserveSeconds = $fallbackEnabled && $totalTimeout > 1
            ? min(30, max(1, (int) floor($totalTimeout / 3)))
            : 0;
        $primaryDeadline = $deadline - ($fallbackReserveSeconds * 1_000_000_000);

        if ($primaryDeadline <= hrtime(true)) {
            $primaryDeadline = $deadline;
        }

        try {
            return $this->requestTextForModel(
                $systemInstruction,
                $contents,
                $generationConfig,
                $apiKey,
                $baseUrl,
                $model,
                $connectTimeout,
                $timeout,
                $fallbackEnabled ? min(2, $maxAttempts) : $maxAttempts,
                $retryBaseDelay,
                $retryMaxDelay,
                $primaryDeadline,
            );
        } catch (GeminiApiException $exception) {
            $primaryHttpStatus = $exception->providerHttpStatus ?? $exception->statusCode;

            if (! $fallbackEnabled || ! in_array($primaryHttpStatus, [404, 429, 500, 503], true)) {
                throw $exception;
            }

            if ($deadline <= hrtime(true)) {
                throw $exception;
            }

            Log::info('Trying the fallback Gemini model.', [
                'primary_model' => $model,
                'fallback_model' => $fallbackModel,
                'primary_http_status' => $primaryHttpStatus,
            ]);

            try {
                return $this->requestTextForModel(
                    $systemInstruction,
                    $contents,
                    $generationConfig,
                    $apiKey,
                    $baseUrl,
                    $fallbackModel,
                    $connectTimeout,
                    $timeout,
                    1,
                    $retryBaseDelay,
                    $retryMaxDelay,
                    $deadline,
                );
            } catch (GeminiApiException $fallbackException) {
                $fallbackHttpStatus = $fallbackException->providerHttpStatus ?? $fallbackException->statusCode;

                if ($fallbackHttpStatus === 429) {
                    throw new GeminiApiException(
                        'Batas penggunaan Gemini tercapai pada model utama dan cadangan. Tunggu beberapa menit lalu coba kembali.',
                        429,
                        $fallbackException,
                        429,
                    );
                }

                if (in_array($fallbackHttpStatus, [500, 503], true)) {
                    throw new GeminiApiException(
                        'Model Gemini utama dan cadangan sedang tidak tersedia atau melewati batas waktu. Coba beberapa saat lagi.',
                        503,
                        $fallbackException,
                        $fallbackHttpStatus,
                    );
                }

                throw $fallbackException;
            }
        }
    }

    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     * @param  array<string, mixed>  $generationConfig
     */
    private function requestTextForModel(
        string $systemInstruction,
        array $contents,
        array $generationConfig,
        string $apiKey,
        string $baseUrl,
        string $model,
        int $connectTimeout,
        int $timeout,
        int $maxAttempts,
        int $retryBaseDelay,
        int $retryMaxDelay,
        int $deadline,
    ): string {
        $url = rtrim($baseUrl, '/').'/models/'.rawurlencode($model).':generateContent';
        $response = null;
        $connectionException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $remainingNanoseconds = $deadline - hrtime(true);

            if ($remainingNanoseconds <= 0) {
                break;
            }

            $attemptTimeout = min($timeout, max(1, (int) ceil($remainingNanoseconds / 1_000_000_000)));
            $attemptConnectTimeout = min($connectTimeout, $attemptTimeout);

            try {
                $response = Http::acceptJson()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->connectTimeout($attemptConnectTimeout)
                    ->timeout($attemptTimeout)
                    ->post($url, [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemInstruction]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => $generationConfig,
                    ]);
                $connectionException = null;
            } catch (ConnectionException $exception) {
                $response = null;
                $connectionException = $exception;
                $errorType = $this->connectionErrorType($exception);

                Log::warning('Gemini request encountered a connection failure.', [
                    'model' => trim($model),
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'exception_type' => $exception::class,
                    'error_type' => $errorType,
                    'error_summary' => $errorType === 'timeout'
                        ? 'Gemini request timed out.'
                        : 'Gemini connection failed.',
                ]);

                if ($attempt < $maxAttempts && $this->pauseBeforeRetry($attempt, $retryBaseDelay, $retryMaxDelay, $deadline)) {
                    continue;
                }

                break;
            }

            if ($response->successful()) {
                break;
            }

            if (in_array($response->status(), [500, 503], true)) {
                Log::warning('Gemini returned a temporary server error.', [
                    'model' => trim($model),
                    'http_status' => $response->status(),
                    'provider_status' => $this->providerStatus($response),
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                ]);

                if ($attempt < $maxAttempts && $this->pauseBeforeRetry($attempt, $retryBaseDelay, $retryMaxDelay, $deadline)) {
                    continue;
                }
            }

            break;
        }

        if ($connectionException instanceof ConnectionException) {
            $errorType = $this->connectionErrorType($connectionException);

            Log::error('Gemini request exhausted its connection retry budget.', [
                'model' => trim($model),
                'max_attempts' => $maxAttempts,
                'exception_type' => $connectionException::class,
                'error_type' => $errorType,
                'error_summary' => $errorType === 'timeout'
                    ? 'Gemini request timed out.'
                    : 'Gemini connection failed.',
            ]);

            throw new GeminiApiException(
                'Gemini tidak dapat dijangkau atau melewati batas waktu. Periksa koneksi server lalu coba kembali.',
                503,
                $connectionException,
            );
        }

        if (! $response instanceof Response) {
            Log::error('Gemini request ended without a response.', [
                'model' => trim($model),
                'error_type' => 'timeout',
                'error_summary' => 'Gemini request deadline expired before a response was received.',
            ]);

            throw new GeminiApiException(
                'Gemini tidak dapat dijangkau atau melewati batas waktu. Periksa koneksi server lalu coba kembali.',
                503,
            );
        }

        if (! $response->successful()) {
            $this->throwForResponse($response, trim($model));
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            Log::warning('Gemini returned an empty or unusable response.', [
                'model' => trim($model),
                'http_status' => $response->status(),
            ]);

            throw new GeminiApiException(
                'Gemini tidak mengembalikan jawaban yang dapat digunakan. Silakan coba kembali.',
                502,
            );
        }

        return trim($text);
    }

    /**
     * @param  list<array{role: string, parts: list<array{text: string}>}>  $contents
     * @return array<string, mixed>
     */
    public function generateJson(string $systemInstruction, array $contents): array
    {
        $text = $this->generateText($systemInstruction, $contents, structured: true);
        $decoded = json_decode($text, true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Gemini returned invalid JSON.', [
                'json_error' => json_last_error_msg(),
            ]);

            throw new GeminiApiException(
                'Format jawaban Gemini tidak valid. Silakan coba kembali.',
                502,
            );
        }

        return $decoded;
    }

    private function pauseBeforeRetry(int $attempt, int $baseDelay, int $maxDelay, int $deadline): bool
    {
        $delay = min($maxDelay, $baseDelay * (2 ** ($attempt - 1)));
        $remainingNanoseconds = $deadline - hrtime(true);

        if ($remainingNanoseconds <= $delay * 1_000_000) {
            return false;
        }

        Log::info('Retrying a temporary Gemini failure.', [
            'attempt' => $attempt,
            'next_delay_ms' => $delay,
        ]);
        Sleep::for($delay)->milliseconds();

        return true;
    }

    private function connectionErrorType(ConnectionException $exception): string
    {
        return Str::contains(Str::lower($exception->getMessage()), ['timeout', 'timed out'])
            ? 'timeout'
            : 'connection';
    }

    private function throwForResponse(Response $response, string $model): never
    {
        $providerMessage = data_get($response->json(), 'error.message');
        $providerCode = data_get($response->json(), 'error.code');
        $status = $response->status();

        Log::error('Gemini API rejected the request.', [
            'model' => $model,
            'http_status' => $status,
            'provider_code' => is_numeric($providerCode) ? (int) $providerCode : null,
            'provider_status' => $this->providerStatus($response),
            'provider_message_summary' => $this->providerMessageSummary($response),
            'error_summary' => match ($status) {
                400 => 'Gemini rejected the request as invalid.',
                401 => 'Gemini did not accept the API key.',
                403 => 'Gemini denied access to the request.',
                404 => 'The configured model was not found.',
                429 => 'Gemini rate or quota limit was reached.',
                500, 503 => 'Gemini is temporarily unavailable.',
                default => 'Gemini rejected the request.',
            },
        ]);

        $message = match ($status) {
            400 => is_string($providerMessage)
                && Str::contains(Str::lower($providerMessage), ['api key not valid', 'invalid api key', 'api key is invalid'])
                    ? 'API key Gemini ditolak. Periksa nilai GEMINI_API_KEY pada konfigurasi server.'
                    : 'Gemini menolak format permintaan. Periksa model dan konfigurasi Gemini.',
            401 => 'API key Gemini tidak valid atau tidak diterima. Periksa konfigurasi server.',
            403 => 'Akses ke Gemini ditolak. Periksa izin API key dan akses layanan Gemini.',
            404 => 'Model Gemini tidak ditemukan. Periksa konfigurasi GEMINI_MODEL dan GEMINI_FALLBACK_MODEL pada server.',
            429 => 'Batas penggunaan Gemini tercapai. Tunggu sebentar lalu coba kembali.',
            500 => 'Layanan Gemini mengalami gangguan sementara. Silakan coba kembali.',
            503 => 'Gemini sedang padat atau sementara tidak tersedia. Coba kembali beberapa saat lagi.',
            default => $status >= 500
                ? 'Layanan Gemini sedang mengalami gangguan. Silakan coba kembali.'
                : 'Gemini gagal memproses permintaan. Periksa konfigurasi lalu coba kembali.',
        };
        $applicationStatus = match ($status) {
            429 => 429,
            500, 503 => 503,
            default => 502,
        };

        throw new GeminiApiException($message, $applicationStatus, providerHttpStatus: $status);
    }

    private function providerMessageSummary(Response $response): ?string
    {
        $providerMessage = data_get($response->json(), 'error.message');

        if (! is_string($providerMessage) || trim($providerMessage) === '') {
            return null;
        }

        $apiKey = config('services.gemini.api_key');
        $summary = strip_tags($providerMessage);

        if (is_string($apiKey) && $apiKey !== '') {
            $summary = str_replace($apiKey, '[redacted]', $summary);
        }

        $summary = Str::squish(preg_replace('/[\r\n\t]+/', ' ', $summary) ?? '');

        return $summary === '' ? null : Str::limit($summary, 180);
    }

    private function providerStatus(Response $response): ?string
    {
        $providerStatus = data_get($response->json(), 'error.status');

        if (! is_string($providerStatus)) {
            return null;
        }

        return Str::limit(preg_replace('/[^A-Z0-9_]/', '', Str::upper($providerStatus)) ?? '', 40, '');
    }
}
