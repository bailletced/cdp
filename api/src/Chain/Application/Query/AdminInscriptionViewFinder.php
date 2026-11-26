<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\AdminInscriptionView;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;

interface AdminInscriptionViewFinder
{
    /**
     * The inscriptions of an edition, ordered by slot rank then display
     * name (US-16.2): the organizer reads the list slot by slot. An optional
     * slot narrows the list to that slot.
     *
     * @return list<AdminInscriptionView>
     */
    public function findByEdition(EditionId $editionId, ?SlotId $slotId = null): array;
}
