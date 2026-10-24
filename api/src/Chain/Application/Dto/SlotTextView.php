<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * The reading page of a slot (F12-T01, US-12.1): its Paris day and label,
 * its edition, and its full text — null while no text is attributed
 * (RG-TX-2).
 */
final readonly class SlotTextView
{
    public function __construct(
        public string $slotId,
        public string $editionId,
        public string $editionTitle,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public string $label,
        public string $dayLabel,
        public ?SlotTextContent $text,
    ) {
    }
}
