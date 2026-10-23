<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\EditionVersionConflictException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;

#[AsCommandHandler]
final readonly class UpdateEditionHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private InscriptionRepositoryInterface $inscriptionRepository,
        private ParisCalendar $calendar,
    ) {
    }

    public function __invoke(UpdateEdition $command): UpdateEditionResult
    {
        $edition = $this->editionRepository->findById($command->editionId);

        if (null === $edition) {
            throw new EditionNotFoundException();
        }

        if ($edition->getVersion() !== $command->expectedVersion) {
            throw new EditionVersionConflictException();
        }

        $startsAtUtc = $this->calendar->fromParisLocal($command->startsAtParisLocal);
        $opensAtUtc = $this->calendar->fromParisLocal($command->opensAtParisLocal);

        // RG-ED-3: the start time is locked as soon as the first inscription exists.
        $hasInscriptions = $this->inscriptionRepository->countByEdition($command->editionId) > 0;

        $edition->reschedule($startsAtUtc, $opensAtUtc, $this->calendar, $hasInscriptions);
        $edition->updateDetails($command->title, $command->presentation);

        $firstSlot = $edition->getSlots()[0];
        $label = SlotLabel::for(new TimeSlot($firstSlot->getStartsAt(), $firstSlot->getEndsAt()));

        return new UpdateEditionResult($startsAtUtc, $label);
    }
}
