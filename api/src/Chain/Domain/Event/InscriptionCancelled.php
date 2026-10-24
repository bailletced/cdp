<?php

declare(strict_types=1);

namespace App\Chain\Domain\Event;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Raised when a priant cancelled an inscription (F14-T05): the planning
 * refreshes (Mercure signal, F18). No e-mail, no alert (RG-EP-1).
 */
final readonly class InscriptionCancelled implements DomainEvent
{
    public function __construct(
        public InscriptionId $inscriptionId,
        public PriantId $priantId,
        public EditionId $editionId,
        public SlotId $slotId,
    ) {
    }
}
