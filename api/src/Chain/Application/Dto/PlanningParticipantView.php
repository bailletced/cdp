<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * A priant registered on a slot of the planning (F10-T08).
 *
 * Read for everyone by the finder; the provider exposes the name and the
 * pointage only to whom the VisibilityPolicy allows (RG-PL-4, RG-LV-2).
 */
final readonly class PlanningParticipantView
{
    public function __construct(
        public string $inscriptionId,
        public string $priantId,
        public string $displayName,
        public bool $prayed,
    ) {
    }
}
