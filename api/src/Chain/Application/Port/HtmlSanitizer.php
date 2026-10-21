<?php

declare(strict_types=1);

namespace App\Chain\Application\Port;

/**
 * Sanitizes rich-text HTML (T27): only p, br, strong, em and blockquote
 * survive, without any attribute. Implementations must be idempotent and
 * safe against XSS payloads.
 */
interface HtmlSanitizer
{
    public function sanitize(string $html): string;
}
