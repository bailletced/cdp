<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\Event\PriantRenamed;
use App\Chain\Domain\Exception\PriantNotFoundException;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Event\EventBusInterface;

/**
 * Handles RenamePriant (F14-T03): the name is carried by the priant, never
 * by its inscriptions, so the new one shows everywhere at once — grid,
 * « En ce moment », relay, signed intentions, history (RG-IN-2). The same
 * name changes nothing and announces nothing.
 */
#[AsCommandHandler]
final readonly class RenamePriantHandler
{
    public function __construct(
        private PriantRepositoryInterface $priantRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(RenamePriant $command): void
    {
        $priant = $this->priantRepository->findById($command->priantId);

        if (null === $priant) {
            throw new PriantNotFoundException();
        }

        if ($priant->getDisplayName()->value === $command->displayName->value) {
            return;
        }

        $priant->rename($command->displayName);
        $this->eventBus->dispatch(new PriantRenamed($priant->getId(), $command->displayName));
    }
}
