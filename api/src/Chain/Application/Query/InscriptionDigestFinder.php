<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\InscriptionDigest;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;

/**
 * Read model of a priant's slots in an edition (F11-T03), for the
 * confirmation email and the agenda file.
 */
interface InscriptionDigestFinder
{
    /**
     * The slots of the priant in the edition, with their full texts; null
     * when the priant has no inscription in it.
     */
    public function findByPriantAndEdition(PriantId $priantId, EditionId $editionId): ?InscriptionDigest;
}
