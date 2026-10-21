<?php

declare(strict_types=1);

namespace App\Chain\Domain\Event;

use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Domain\Event\EventInterface;

final readonly class EditionUnscheduled implements EventInterface
{
    public function __construct(
        public EditionId $editionId,
        public \DateTimeImmutable $unscheduledAt,
    ) {
    }
}
