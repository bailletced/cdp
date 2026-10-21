<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * Full details of one text of the library (US-06.1): body and hints included,
 * with the attribution count.
 */
final readonly class AdminTextDetailsView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $source,
        public string $body,
        public ?string $hints,
        public int $attributionCount,
    ) {
    }
}
