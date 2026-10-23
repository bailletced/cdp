<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\PriantDirectory;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PriantDirectory implemented by the Access BC (F09-T09).
 *
 * Domain event listeners run on the event bus, which has NO surrounding
 * transaction: this adapter flushes explicitly (unlike the repositories,
 * which never flush themselves).
 */
final readonly class AccountPriantDirectory implements PriantDirectory
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[\Override]
    public function linkAccountToPriant(Email $email, string $priantId): void
    {
        $account = $this->accountRepository->findByEmail($email);

        if (null === $account) {
            $this->accountRepository->add(new Account(new AccountId(), $email, priantId: $priantId));
            $this->entityManager->flush();

            return;
        }

        if ($account->getPriantId() === $priantId) {
            return; // Already linked: idempotent, nothing to flush.
        }

        $account->updatePriantId($priantId);
        $this->entityManager->flush();
    }
}
