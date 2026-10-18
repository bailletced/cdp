<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Account;
use App\Access\Domain\Organizer;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Handles the AddOrganizer command inside the command bus transaction: the
 * repositories only register the aggregates, the doctrine_transaction middleware
 * flushes and commits at the end of the handling.
 */
#[AsCommandHandler]
final readonly class AddOrganizerHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private OrganizerRepositoryInterface $organizerRepository,
    ) {
    }

    public function __invoke(AddOrganizer $command): AddOrganizerResult
    {
        $account = $this->accountRepository->findByEmail($command->email);

        if ($account === null) {
            $account = new Account(new AccountId(), $command->email);
            $this->accountRepository->add($account);
            $outcome = AddOrganizerResult::AccountCreated;
        } else {
            $outcome = AddOrganizerResult::OrganizerCreated;
        }

        // Guard against a concurrent creation between the lookup and the insert
        if ($this->organizerRepository->isOrganizer($account->getId()->value)) {
            return AddOrganizerResult::AlreadyOrganizer;
        }

        $this->organizerRepository->add(new Organizer($account->getId()->value, $account->getId()->value));

        return $outcome;
    }
}
