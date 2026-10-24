<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * An empty slot still to come (F13, RG-PL-2): what the banner lists and what
 * « Prendre un créneau vide » or the suggestion after an inscription offer —
 * the Paris label and the title of the text, never a priant.
 */
final readonly class EmptySlotView
{
    public function __construct(
        public string $id,
        public int $rank,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public string $label,
        public ?string $textTitle,
        public ?string $textSource,
    ) {
    }
}
