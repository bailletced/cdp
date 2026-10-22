<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Assigns (or withdraws, with null) a text to a single slot (US-07.1).
 *
 * The edition is located through the slot identifier; the text is verified
 * to exist before the assignment (a null textId withdraws the attribution).
 */
#[AsCommandHandler]
final readonly class AssignTextHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private TextRepositoryInterface $textRepository,
    ) {
    }

    public function __invoke(AssignText $command): void
    {
        $edition = $this->editionRepository->findBySlotId($command->slotId);

        if (null === $edition) {
            throw new EditionNotFoundException();
        }

        if (null !== $command->textId && null === $this->textRepository->findById($command->textId)) {
            throw new TextNotFoundException();
        }

        $edition->assignText($command->slotId, $command->textId);
    }
}
