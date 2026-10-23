<?php

declare(strict_types=1);

namespace App\Chain\Domain\Event;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Raised when a priant has been registered on one or more slots (F09-T07).
 * Carries the slot list so listeners (planning refresh, notifications) do
 * not have to reload the inscriptions.
 */
final readonly class InscriptionsRegistered implements DomainEvent
{
    /**
     * @param list<SlotId> $slotIds
     */
    public function __construct(
        public PriantId $priantId,
        public EditionId $editionId,
        public array $slotIds,
    ) {
    }
}
