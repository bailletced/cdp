<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\Event\InscriptionCancelled;
use App\Chain\Domain\Exception\InscriptionNotFoundException;
use App\Chain\Domain\Exception\SlotAlreadyStartedException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Event\EventBusInterface;
use Psr\Clock\ClockInterface;

/**
 * Handles CancelInscription (F14-T05): RG-EP-1 — free cancellation until the
 * slot starts. No e-mail is sent and no organizer is alerted (§5.7): the
 * event only refreshes the planning, where the slot shows « À pourvoir »
 * again when it was the only priant.
 */
#[AsCommandHandler]
final readonly class CancelInscriptionHandler
{
    public function __construct(
        private InscriptionRepositoryInterface $inscriptionRepository,
        private EditionRepositoryInterface $editionRepository,
        private EventBusInterface $eventBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CancelInscription $command): void
    {
        $inscription = $this->inscriptionRepository->findById($command->inscriptionId);

        if (null === $inscription) {
            throw new InscriptionNotFoundException();
        }

        $slot = array_find(
            $this->editionRepository->findById($inscription->getEditionId())?->getSlots() ?? [],
            static fn (Slot $slot): bool => $slot->getId()->equals($inscription->getSlotId()),
        );

        if (null === $slot || $this->clock->now() >= $slot->getStartsAt()) {
            throw new SlotAlreadyStartedException();
        }

        $this->inscriptionRepository->remove($inscription);
        $this->eventBus->dispatch(new InscriptionCancelled(
            $inscription->getId(),
            $inscription->getPriantId(),
            $inscription->getEditionId(),
            $inscription->getSlotId(),
        ));
    }
}
