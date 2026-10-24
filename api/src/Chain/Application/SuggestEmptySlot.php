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
     * Null when the chain is complete, or for a draft or unknown edition.
     *
     * @param list<SlotId> $excluded
     */
    public function __invoke(EditionId $editionId, ?SlotId $near = null, array $excluded = []): ?EmptySlotView
    {
        return $this->slotPicker->pick(
            $this->emptySlotsFinder->findByEdition($editionId),
            null === $near ? null : $this->rankOf($editionId, $near),
            array_map(static fn (SlotId $slotId): string => (string) $slotId, $excluded),
        );
    }

    private function rankOf(EditionId $editionId, SlotId $slotId): ?int
    {
        $slot = array_find(
            $this->editionRepository->findById($editionId)?->getSlots() ?? [],
            static fn (Slot $slot): bool => $slot->getId()->equals($slotId),
        );

        return $slot?->getRank();
    }
}
