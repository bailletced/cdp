<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\PlanningView;
use App\Chain\Domain\ValueObject\EditionId;

/**
 * Read model for the public planning grid (F08-T03).
 */
interface PlanningViewFinder
{
    /**
     * Returns the planning of the given edition, or null when the edition
     * does not exist or is not publicly visible (draft).
     */
    public function findByEdition(EditionId $editionId): ?PlanningView;
}
