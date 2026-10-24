<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * « Mon espace » (F14-T01, US-14.1): the current name of the priant and its
 * slots of an edition, in chronological order.
 */
final readonly class MySpaceView
{
    /**
     * @param list<MyInscriptionView> $inscriptions
     */
    public function __construct(
        public string $displayName,
        public string $editionId,
        public string $editionTitle,
        public array $inscriptions,
    ) {
    }
}
