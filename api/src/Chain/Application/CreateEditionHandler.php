<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\Time\SlotLabel;

#[AsCommandHandler]
final readonly class CreateEditionHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private ParisCalendar $calendar,
    ) {
    }

    public function __invoke(CreateEdition $command): CreateEditionResult
    {
        $startsAtUtc = $this->calendar->fromParisLocal($command->startsAtParisLocal);
        $opensAtUtc = $this->calendar->fromParisLocal($command->opensAtParisLocal);

        $edition = Edition::create(
            $command->title,
            $command->presentation,
            $startsAtUtc,
            $opensAtUtc,
            $this->calendar,
        );

        $this->editionRepository->add($edition);

        $firstSlot = $edition->getSlots()[0];
        $label = SlotLabel::for(new \App\Shared\Domain\Time\TimeSlot($firstSlot->getStartsAt(), $firstSlot->getEndsAt()));

        return new CreateEditionResult($edition->getId(), $startsAtUtc, $label);
    }
}
