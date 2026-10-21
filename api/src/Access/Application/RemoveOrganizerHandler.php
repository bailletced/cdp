<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Exception\LastOrganizerException;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Handles the RemoveOrganizer command inside the command bus transaction.
 * Refuses to remove the last remaining organizer (RG: at least one must stay).
 */
#[AsCommandHandler]
final readonly class RemoveOrganizerHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private OrganizerRepositoryInterface $organizerRepository,
    ) {
    }

    public function __invoke(RemoveOrganizer $command): void
    {
        $account = $this->accountRepository->findByEmail($command->email);

        if (null === $account) {
            return;
        }

        $organizer = $this->organizerRepository->findByAccountId($account->getId()->value);

        if (null === $organizer) {
            return;
        }

        if (1 === $this->organizerRepository->countAll()) {
            throw new LastOrganizerException();
        }

        $this->organizerRepository->remove($organizer);
    }
}
