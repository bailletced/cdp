<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Distributes an ordered list of texts across the slots of an edition (US-07.2).
 *
 * Every text is verified to exist before the distribution; the list is
 * truncated at slot 48 by the aggregate.
 */
#[AsCommandHandler]
final readonly class DistributeTextsHandler
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private TextRepositoryInterface $textRepository,
    ) {
    }

    public function __invoke(DistributeTexts $command): void
    {
        $edition = $this->editionRepository->findById($command->editionId);

        if (null === $edition) {
            throw new EditionNotFoundException();
        }

        foreach ($command->textIds as $textId) {
            if (null === $this->textRepository->findById($textId)) {
                throw new TextNotFoundException();
            }
        }

        $edition->distributeTexts($command->textIds, $command->fromRank);
    }
}
