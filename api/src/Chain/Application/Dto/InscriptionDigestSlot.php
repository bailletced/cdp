<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * One slot of a priant, as recalled in the confirmation email and its
 * agenda file (F11): the Paris label, the full text when attributed
 * (RG-TX-1) and what the .ics needs (instants in UTC, inscription version).
 */
final readonly class InscriptionDigestSlot
{
    public function __construct(
        public string $inscriptionId,
        public int $inscriptionVersion,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public string $label,
        public ?string $textTitle,
        public ?string $textSource,
        public ?string $textBody,
        public ?string $textHints,
    ) {
    }
}
