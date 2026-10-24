<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\MySpaceView;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;

/**
 * Read model of « Mon espace » (F14-T01).
 */
interface MySpaceViewFinder
{
    /**
     * The name of the priant and its slots of the edition — none when it
     * has no inscription there. Null when the priant or the edition does
     * not exist.
     */
    public function find(PriantId $priantId, EditionId $editionId): ?MySpaceView;
}
