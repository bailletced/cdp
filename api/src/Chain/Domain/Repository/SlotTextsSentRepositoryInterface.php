<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\SlotTextsSent;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;

interface SlotTextsSentRepositoryInterface
{
    /**
     * The delivery of the slot texts email to this priant for this edition,
     * or null when they have not received it yet.
     */
    public function findByPriantAndEdition(PriantId $priantId, EditionId $editionId): ?SlotTextsSent;

    /**
     * Add a delivery to the repository. The aggregate is persisted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function add(SlotTextsSent $delivery): void;
}
