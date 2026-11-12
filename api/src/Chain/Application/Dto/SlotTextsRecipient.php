<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;

/**
 * A priant who can receive the texts of their slots by e-mail (F24): a
 * distinct recipient of the edition, with an e-mail address.
 */
final readonly class SlotTextsRecipient
{
    public function __construct(
        public PriantId $priantId,
        public string $displayName,
        public Email $email,
    ) {
    }
}
