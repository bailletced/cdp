<?php

declare(strict_types=1);

namespace App\Chain\Domain\Event;

use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Raised when a priant changed its display name (F14-T03): the planning and
 * the live views refresh (Mercure signal, F18).
 */
final readonly class PriantRenamed implements DomainEvent
{
    public function __construct(
        public PriantId $priantId,
        public DisplayName $displayName,
    ) {
    }
}
