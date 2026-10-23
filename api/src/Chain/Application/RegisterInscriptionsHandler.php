<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\Event\InscriptionsRegistered;
use App\Chain\Domain\Exception\EditionClosedForInscriptionsException;
use App\Chain\Domain\Exception\SlotAlreadyPastException;
use App\Chain\Domain\Exception\SlotNotFoundException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\Event\Integration\PriantRegistered;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * Handles RegisterInscriptions (F09-T07): finds the priant by email or
 * creates it with the given display name, records the consent, and adds one
 * inscription per requested slot.
 *
 * RG-IN-2: one email = one priant = one display name. A registration form
 * NEVER overwrites an existing display name: when the priant is already
 * known, the name carried by the priant wins. The result exposes the ACTUAL
 * display name so the API can echo it back.
 *
 * RG-IN-3: one inscription per (slot, priant) — the repository rejects a
 * duplicate (AlreadyRegisteredException, enforced up to the database).
 *
 * Temporal rules (F09-T08): inscriptions are possible only while the edition
 * is open or running, and only on the current slot or a future one.
 *
 * @see specs-fonctionnelles.md §5.2, §5.4, RG-IN-1 to RG-IN-5
 */
#[AsCommandHandler]
final readonly class RegisterInscriptionsHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private PriantRepositoryInterface $priantRepository,
        private InscriptionRepositoryInterface $inscriptionRepository,
        private EventBusInterface $eventBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RegisterInscriptions $command): RegisterInscriptionsResult
    {
        if ([] === $command->slotIds) {
            throw new InvalidArgumentException('Au moins un créneau est requis.');
        }

        $edition = $this->editionRepository->findBySlotId($command->slotIds[0]);

        if (null === $edition) {
            throw new SlotNotFoundException();
        }

        $now = $this->clock->now();

        // Temporal rule: inscriptions are open only while the edition is open
        // or running (specs-fonctionnelles.md §5.2).
        $status = $edition->statusAt($now);
        if (!\in_array($status, ['open', 'running'], true)) {
            throw new EditionClosedForInscriptionsException();
        }

        // Every requested slot must belong to the edition of the first one:
        // a registration always targets a single edition. A past slot is
        // refused: only the current slot and future slots can be taken.
        $slots = [];
        foreach ($command->slotIds as $slotId) {
            $slot = $this->findSlot($edition->getSlots(), $slotId);

            if ($now >= $slot->getEndsAt()) {
                throw new SlotAlreadyPastException();
            }

            $slots[] = $slot;
        }

        // RG-IN-2: an existing priant keeps its display name; a null email
        // never matches and always creates a new priant.
        $priant = null === $command->email ? null : $this->priantRepository->findByEmail($command->email);
        $isNewPriant = null === $priant;

        if ($isNewPriant) {
            $priant = new Priant($command->email, $command->displayName);
        }

        if ($command->consent) {
            $priant->giveConsent($now);
        }

        if ($isNewPriant) {
            $this->priantRepository->add($priant);
        }

        $inscriptions = [];
        foreach ($slots as $slot) {
            $inscription = new Inscription($edition->getId(), $slot->getId(), $priant->getId());
            $this->inscriptionRepository->add($inscription);
            $inscriptions[] = $inscription;
        }

        $this->eventBus->dispatch(new InscriptionsRegistered(
            $priant->getId(),
            $edition->getId(),
            array_map(static fn (Slot $slot): SlotId => $slot->getId(), $slots),
        ));

        if ($isNewPriant) {
            // Integration event (Shared) so the Access BC can link the account
            // to the priant without depending on Chain (F09-T09).
            $this->eventBus->dispatch(new PriantRegistered($priant->getId()->value->toRfc4122(), $command->email));
        }

        return new RegisterInscriptionsResult($priant->getId(), $priant->getDisplayName(), $inscriptions);
    }

    /**
     * @param list<Slot> $slots
     */
    private function findSlot(array $slots, SlotId $slotId): Slot
    {
        foreach ($slots as $slot) {
            if ($slot->getId()->equals($slotId)) {
                return $slot;
            }
        }

        throw new SlotNotFoundException();
    }
}
