<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

use App\Shared\Domain\ValueObject\Email;

/**
 * Everything the confirmation email of an inscription carries (F11, RG-IN-1):
 * the slots taken at once with their texts, the personal link and the
 * agenda file.
 */
final readonly class InscriptionConfirmation
{
    /**
     * @param list<InscriptionDigestSlot> $slots
     */
    public function __construct(
        public Email $recipient,
        public string $displayName,
        public string $editionTitle,
        public array $slots,
        public string $personalLink,
        public string $calendar,
    ) {
    }
}
