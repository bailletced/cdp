<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * Public planning view of an edition (US-08.2): the 48 slots in
 * chronological order, each with its time window, part of day, temporal
 * state and attributed text (title + source only).
 */
final readonly class PlanningView
{
    /**
     * @param PlanningSlotView[] $slots
     */
    public function __construct(
        public string $editionId,
        public array $slots,
    ) {
    }
}
