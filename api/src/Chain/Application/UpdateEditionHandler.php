<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\EditionVersionConflictException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;

#[AsCommandHandler]
final readonly class UpdateEditionHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
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

        $hasInscriptions = false; // Wired in F09

        $edition->reschedule($startsAtUtc, $opensAtUtc, $this->calendar, $hasInscriptions);
        $this->updateTitle($edition, $command->title);
        $this->updatePresentation($edition, $command->presentation);

        $firstSlot = $edition->getSlots()[0];
        $label = SlotLabel::for(new TimeSlot($firstSlot->getStartsAt(), $firstSlot->getEndsAt()));

        return new UpdateEditionResult($startsAtUtc, $label);
    }

    private function updateTitle(Edition $edition, EditionTitle $title): void
    {
        $reflection = new \ReflectionProperty(Edition::class, 'title');
        $reflection->setValue($edition, $title);
    }

    private function updatePresentation(Edition $edition, Presentation $presentation): void
    {
        $reflection = new \ReflectionProperty(Edition::class, 'presentation');
        $reflection->setValue($edition, $presentation);
    }
}
