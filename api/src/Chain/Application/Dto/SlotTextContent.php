<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * The full text attributed to a slot (RG-TX-1): the body is the sanitized
 * HTML stored by the library (T27), the hints are plain text.
 */
final readonly class SlotTextContent
{
    public function __construct(
        public string $title,
        public string $source,
        public string $body,
        public ?string $hints,
    ) {
    }
}
