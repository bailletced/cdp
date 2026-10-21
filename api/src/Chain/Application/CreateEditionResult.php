<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\EditionId;

final readonly class CreateEditionResult
{
    public function __construct(
        public EditionId $editionId,
        public \DateTimeImmutable $resolvedStartsAtUtc,
        public string $resolvedStartsAtLabel,
    ) {
    }
}
