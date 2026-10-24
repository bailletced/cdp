<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Application\Dto\EmptySlotView;
use App\Chain\Application\Port\SlotPicker;
use App\Chain\Application\Query\EmptySlotsFinder;
use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;

/**
 * Suggests an empty slot still to come of a public edition (F13, §5.3):
 * « Prendre un créneau vide », and the suggestion after an inscription,
 * next to the slot just taken.
 */
final readonly class SuggestEmptySlot
{
    public function __construct(
        private EmptySlotsFinder $emptySlotsFinder,
        private EditionRepositoryInterface $editionRepository,
        private SlotPicker $slotPicker,
    ) {
    }

    /**
     * The adjacent slots are looked for around the latest of $near (the
     * last slot taken, US-13.3). Null when the chain is complete, or for a
     * draft or unknown edition.
     *
     * @param list<SlotId> $near
     * @param list<SlotId> $excluded
     */
    public function __invoke(EditionId $editionId, array $near = [], array $excluded = []): ?EmptySlotView
    {
        return $this->slotPicker->pick(
            $this->emptySlotsFinder->findByEdition($editionId),
            $this->latestRankOf($editionId, $near),
            array_map(static fn (SlotId $slotId): string => (string) $slotId, $excluded),
        );
    }

    /**
     * @param list<SlotId> $slotIds
     */
    private function latestRankOf(EditionId $editionId, array $slotIds): ?int
    {
        if ([] === $slotIds) {
            return null;
        }

        $ranks = array_map(
            static fn (Slot $slot): int => $slot->getRank(),
            array_filter(
                $this->editionRepository->findById($editionId)?->getSlots() ?? [],
                static fn (Slot $slot): bool => array_any($slotIds, static fn (SlotId $slotId): bool => $slot->getId()->equals($slotId)),
            ),
        );

        return [] === $ranks ? null : max($ranks);
    }
}
