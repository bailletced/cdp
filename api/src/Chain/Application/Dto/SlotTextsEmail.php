<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

use App\Shared\Domain\ValueObject\Email;

/**
 * The e-mail carrying the texts of all the slots a priant has taken in an
 * edition (F24): recipient, greeting name and the digest of their slots.
 */
final readonly class SlotTextsEmail
{
    public function __construct(
        public Email $recipient,
        public string $displayName,
        public InscriptionDigest $digest,
    ) {
    }
}
