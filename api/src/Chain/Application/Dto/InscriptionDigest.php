<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * The slots of a priant in an edition, with their texts (F11-T03).
 */
final readonly class InscriptionDigest
{
    /**
     * @param list<InscriptionDigestSlot> $slots In chronological order
     */
    public function __construct(
        public string $editionTitle,
        public \DateTimeImmutable $editionEndsAt,
        public array $slots,
    ) {
    }
}
