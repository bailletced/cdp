<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\PublicEditionView;

/**
 * Read model for the public planning page (F08).
 *
 * RG-ED-1: at most one public edition — the most recent scheduled one
 * whose opening date has been reached.
 */
interface PublicEditionViewFinder
{
    /**
     * Returns the current public edition, or null when none is open.
     */
    public function current(): ?PublicEditionView;
}
