<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Random;

use App\Chain\Application\Dto\EmptySlotView;
use App\Chain\Application\Port\SlotPicker;
use Random\Randomizer;

/**
 * SlotPicker drawing with a Randomizer (F13-T01): its engine is the secure
 * one in production, a seeded one in the tests (§14.1).
 */
final readonly class RandomizerSlotPicker implements SlotPicker
{
    public function __construct(
        private Randomizer $randomizer,
    ) {
    }

    #[\Override]
    public function pick(array $emptySlots, ?int $nearRank, array $excludedIds): ?EmptySlotView
    {
        $candidates = array_values(array_filter(
            $emptySlots,
            static fn (EmptySlotView $slot): bool => !\in_array($slot->id, $excludedIds, true),
        ));

        // « Un autre » as long as possible: the last empty slot comes back
        if ([] === $candidates) {
            $candidates = $emptySlots;
        }

        if ([] === $candidates) {
            return null;
        }

        if (null !== $nearRank) {
            foreach ([$nearRank + 1, $nearRank - 1] as $rank) {
                $adjacent = array_find($candidates, static fn (EmptySlotView $slot): bool => $slot->rank === $rank);

                if (null !== $adjacent) {
                    return $adjacent;
                }
            }
        }

        return $candidates[$this->randomizer->getInt(0, \count($candidates) - 1)];
    }
}
