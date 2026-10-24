<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * View of a single slot in the planning grid (US-08.2, F10-T08).
 *
 * The registration count (RG-PL-1) and the occupied flag are public. The
 * participants (names, pointages) are read for everyone but RG-PL-4 forbids
 * leaking them to visitors: the provider masks them through the
 * VisibilityPolicy.
 */
final readonly class PlanningSlotView
{
    /**
     * @param list<PlanningParticipantView> $participants In registration order
     */
    public function __construct(
        public string $id,
        public int $rank,
        public string $startsAtUtc,
        public string $endsAtUtc,
        public string $label,
        public string $partOfDay,
        public string $state,
        public ?string $textTitle,
        public ?string $textSource,
        public int $registrationCount,
        public bool $occupied,
        public array $participants = [],
    ) {
    }
}
