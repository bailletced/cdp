<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * Public view of the current edition (US-08.1): title, presentation, dates
 * and their Paris labels, computed status. No slot details, no organizer data.
 */
final readonly class PublicEditionView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $presentation,
        public string $startsAtUtc,
        public string $opensAtUtc,
        public string $startsAtLabel,
        public string $opensAtLabel,
        public string $status,
    ) {
    }
}
