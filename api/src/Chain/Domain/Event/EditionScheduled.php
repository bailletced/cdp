<?php

declare(strict_types=1);

namespace App\Chain\Domain\Event;

use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Domain\Event\DomainEvent;

final readonly class EditionScheduled implements DomainEvent
{
    public function __construct(
        public EditionId $editionId,
        public \DateTimeImmutable $scheduledAt,
    ) {
    }
}
