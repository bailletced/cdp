<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class UnscheduleEditionHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(UnscheduleEdition $command): void
    {
        $edition = $this->editionRepository->findById($command->editionId);

        if (null === $edition) {
            throw new EditionNotFoundException();
        }

        $edition->unschedule($this->clock->now());
    }
}
