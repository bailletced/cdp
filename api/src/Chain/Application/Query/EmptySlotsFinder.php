<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\EmptySlotView;
use App\Chain\Domain\ValueObject\EditionId;

/**
 * Read model of the empty slots to fill (F13-T02, §5.3).
 */
interface EmptySlotsFinder
{
    /**
     * The empty slots of a public edition that have not started yet, in
     * chronological order (RG-PL-2); none for a draft or unknown edition.
     *
     * @return list<EmptySlotView>
     */
    public function findByEdition(EditionId $editionId): array;
}
