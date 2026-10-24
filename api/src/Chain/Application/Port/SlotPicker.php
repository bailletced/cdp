<?php

declare(strict_types=1);

namespace App\Chain\Application\Port;

use App\Chain\Application\Dto\EmptySlotView;

/**
 * Draws the empty slot to offer (F13-T01, §5.3): « Prendre un créneau vide »
 * and the suggestion after an inscription.
 */
interface SlotPicker
{
    /**
     * The slot right after the one of rank $nearRank, else the one right
     * before, else one at random — the chance spreads the priants. The
     * excluded slots are skipped as long as another one is left. Null when
     * there is no empty slot.
     *
     * @param list<EmptySlotView> $emptySlots
     * @param list<string>        $excludedIds
     */
    public function pick(array $emptySlots, ?int $nearRank, array $excludedIds): ?EmptySlotView;
}
