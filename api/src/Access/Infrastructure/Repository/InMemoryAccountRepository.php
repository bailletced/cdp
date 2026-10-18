<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Repository;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;

/**
 * In-memory implementation of AccountRepositoryInterface for testing purposes.
 */
final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var array<string, Account> Keyed by AccountId string representation
     */
    private array $accountsById = [];

    /**
     * @var array<string, Account> Keyed by lowercase email
     */
    private array $accountsByEmail = [];

    #[\Override]
    public function findById(AccountId $id): ?Account
    {
        return $this->accountsById[(string) $id] ?? null;
    }

    #[\Override]
    public function findByEmail(Email $email): ?Account
    {
        $lowerEmail = mb_strtolower($email->value);

        return $this->accountsByEmail[$lowerEmail] ?? null;
    }

    #[\Override]
    public function add(Account $account): void
    {
        $id = (string) $account->getId();
        $lowerEmail = mb_strtolower($account->getEmail()->value);

        $this->accountsById[$id] = $account;
        $this->accountsByEmail[$lowerEmail] = $account;
    }

    #[\Override]
    public function remove(Account $account): void
    {
        $id = (string) $account->getId();
        $lowerEmail = mb_strtolower($account->getEmail()->value);

        unset($this->accountsById[$id]);
        unset($this->accountsByEmail[$lowerEmail]);
    }

    #[\Override]
    public function existsByEmail(Email $email): bool
    {
        $lowerEmail = mb_strtolower($email->value);

        return isset($this->accountsByEmail[$lowerEmail]);
    }
}
