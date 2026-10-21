<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\CommandInterface;

/**
 * Updates an existing edition (title, presentation, dates).
 * Dates are given in Paris local time.
 */
final readonly class UpdateEdition implements CommandInterface
{
    public function __construct(
        public EditionId $editionId,
        public EditionTitle $title,
        public Presentation $presentation,
        public \DateTimeImmutable $startsAtParisLocal,
        public \DateTimeImmutable $opensAtParisLocal,
        public int $expectedVersion,
    ) {
    }
}
