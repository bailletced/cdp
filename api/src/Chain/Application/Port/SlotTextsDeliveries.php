<?php

declare(strict_types=1);

namespace App\Chain\Application\Port;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;

/**
 * The deliveries of the slot texts email (F24): which priant already
 * received it for an edition, so the command can run again without
 * sending twice (idempotence).
 */
interface SlotTextsDeliveries
{
    /**
     * Has this priant already received the slot texts email of this edition?
     */
    public function alreadySentTo(PriantId $priantId, EditionId $editionId): bool;

    /**
     * Records durably that the priant received the slot texts email of the
     * edition, at the given instant.
     */
    public function recordDelivery(PriantId $priantId, EditionId $editionId, \DateTimeImmutable $at): void;
}
