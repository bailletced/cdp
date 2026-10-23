<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\PriantDirectory;
use App\Shared\Domain\ValueObject\Email;

/**
 * PriantDirectory implemented by the Access BC (F09-T09).
 *
 * Domain event listeners run on the event bus, wrapped in a transaction by
 * the doctrine_transaction middleware: like the repositories, this adapter
 * persists without flushing — the middleware flushes and commits once the
 * listener returns.
 */
final readonly class AccountPriantDirectory implements PriantDirectory
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    #[\Override]
    public function linkAccountToPriant(Email $email, string $priantId): void
    {
        $account = $this->accountRepository->findByEmail($email);

        if (null === $account) {
            $this->accountRepository->add(new Account(new AccountId(), $email, priantId: $priantId));

            return;
        }

        if ($account->getPriantId() === $priantId) {
            return; // Already linked: idempotent, nothing to persist.
        }

        $account->updatePriantId($priantId);
    }
}
