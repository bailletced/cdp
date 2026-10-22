<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Event\AccountRegistered;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Event\EventBusInterface;

/**
 * Handles Register: creates the account if it does not exist yet, then raises
 * an AccountRegistered domain event. A listener sends the personal login link
 * after the transaction commits (transactional outbox), so the account is
 * guaranteed to be persisted when the link is issued. When the account already
 * exists, only the event is raised — the operation is idempotent and the
 * caller must not learn whether the account was created.
 */
#[AsCommandHandler]
final readonly class RegisterHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(Register $command): void
    {
        if (null === $this->accountRepository->findByEmail($command->email)) {
            $this->accountRepository->add(new Account(new AccountId(), $command->email));
        }

        $this->eventBus->dispatch(new AccountRegistered($command->email));
    }
}
