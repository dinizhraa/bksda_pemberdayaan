<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class DataSanitizer
{
    public function redactContactDetails(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = strip_tags($text);
        $text = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', '[email disamarkan]', $text) ?? $text;
        $text = preg_replace('/(?<!\d)(?:\+?62|0)8\d(?:[\s.-]?\d){7,12}(?!\d)/u', '[nomor telepon disamarkan]', $text) ?? $text;

        return Str::limit(trim($text), 1200);
    }
}
