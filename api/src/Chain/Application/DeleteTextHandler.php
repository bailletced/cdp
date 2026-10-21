<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Exception\TextAttributedToScheduledEditionException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Deletes a text of the library (US-06.3, RG-TX-3).
 *
 * A text attributed to a scheduled edition — whatever its computed status
 * (open, running, finished) — cannot be deleted: the command refuses with a
 * conflict. Attributions on draft editions are withdrawn before the removal.
 */
#[AsCommandHandler]
final readonly class DeleteTextHandler
{
    public function __construct(
        private TextRepositoryInterface $textRepository,
        private EditionRepositoryInterface $editionRepository,
    ) {
    }

    public function __invoke(DeleteText $command): void
    {
        $text = $this->textRepository->findById($command->textId);

        if (null === $text) {
            throw new TextNotFoundException();
        }

        $editions = $this->editionRepository->findAll();

        foreach ($editions as $edition) {
            if (Edition::LIFECYCLE_SCHEDULED === $edition->getLifecycle() && $edition->hasTextAttributed($command->textId)) {
                throw new TextAttributedToScheduledEditionException();
            }
        }

        // Refused above for scheduled editions: the attributions left are on drafts only.
        foreach ($editions as $edition) {
            $edition->removeTextAttributions($command->textId);
        }

        $this->textRepository->remove($text);
    }
}
