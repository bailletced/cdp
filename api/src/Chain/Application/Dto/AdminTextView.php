<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * One row of the admin text library listing (US-06.4): the body is not
 * loaded, only the attribution count is.
 */
final readonly class AdminTextView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $source,
        public int $attributionCount,
    ) {
    }
}
