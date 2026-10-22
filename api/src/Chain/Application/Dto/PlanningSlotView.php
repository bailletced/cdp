<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * Public view of a single slot in the planning grid (US-08.2).
 *
 * No priant names: RG-PL-4 forbids leaking names to visitors. The
 * registration count and "you" flag are added in F09/F10.
 */
final readonly class PlanningSlotView
{
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
    ) {
    }
}
