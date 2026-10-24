<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;

final readonly class RegisterInscriptionsResult
{
    /**
     * @param list<Inscription> $inscriptions
     */
    public function __construct(
        public PriantId $priantId,
        public DisplayName $displayName,
        public array $inscriptions,
        public \DateTimeImmutable $editionEndsAt,
    ) {
    }
}
