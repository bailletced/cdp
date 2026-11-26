<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\Dto\AdminInscriptionView;
use App\Chain\Application\Query\AdminInscriptionViewFinder;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\Time\DayLabel;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;

/**
 * In-memory implementation of AdminInscriptionViewFinder for testing.
 *
 * The views are ordered by slot rank, then display name — the same order as
 * the Doctrine implementation (US-16.2: the list is read slot by slot).
 */
final readonly class InMemoryAdminInscriptionViewFinder implements AdminInscriptionViewFinder
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private PriantRepositoryInterface $priantRepository,
        private InscriptionRepositoryInterface $inscriptionRepository,
    ) {
    }

    public function findByEdition(EditionId $editionId, ?SlotId $slotId = null): array
    {
        $edition = $this->editionRepository->findById($editionId);

        if (null === $edition) {
            return [];
        }

        $inscriptions = $this->inscriptionRepository->findByEdition($editionId);

        $slotsBySort = [];
        foreach ($edition->getSlots() as $slot) {
            if (null !== $slotId && !$slot->getId()->equals($slotId)) {
                continue;
            }

            $slotsBySort[] = $slot;
        }

        $views = [];
        foreach ($slotsBySort as $slot) {
            $ofSlot = array_values(array_filter(
                $inscriptions,
                static fn (\App\Chain\Domain\Entity\Inscription $inscription): bool => $inscription->getSlotId()->equals($slot->getId()),
            ));

            $byName = [];
            foreach ($ofSlot as $inscription) {
                $priant = $this->priantRepository->findById(new PriantId((string) $inscription->getPriantId()));
                $byName[] = [$priant?->getDisplayName()->value ?? '', $inscription, $priant];
            }

            usort($byName, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

            $timeSlot = new TimeSlot($slot->getStartsAt(), $slot->getEndsAt());

            foreach ($byName as [$name, $inscription, $priant]) {
                $views[] = new AdminInscriptionView(
                    inscriptionId: (string) $inscription->getId(),
                    displayName: $name,
                    email: $priant?->getEmail()?->value,
                    origin: $inscription->getOrigin(),
                    slotId: (string) $slot->getId(),
                    slotRank: $slot->getRank(),
                    dayLabel: DayLabel::for($slot->getStartsAt()),
                    slotLabel: SlotLabel::for($timeSlot),
                    startsAtUtc: $slot->getStartsAt()->format(\DateTimeInterface::ATOM),
                    endsAtUtc: $slot->getEndsAt()->format(\DateTimeInterface::ATOM),
                );
            }
        }

        return $views;
    }
}
