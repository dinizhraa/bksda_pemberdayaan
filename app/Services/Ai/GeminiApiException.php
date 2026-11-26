<?php

namespace App\Services\Ai;

use RuntimeException;
use Throwable;

class GeminiApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 503,
        ?Throwable $previous = null,
        public readonly ?int $providerHttpStatus = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
