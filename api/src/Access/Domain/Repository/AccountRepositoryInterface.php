<?php

declare(strict_types=1);

namespace App\Access\Domain\Repository;

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;

/**
 * @template-extends RepositoryInterface<Account>
 */
interface AccountRepositoryInterface
{
    /**
     * Find an account by its ID.
     */
    public function findById(AccountId $id): ?Account;

    /**
     * Find an account by its email (case-insensitive).
     */
    public function findByEmail(Email $email): ?Account;

    /**
     * Add an account to the repository. The aggregate is persisted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function add(Account $account): void;

    /**
     * Remove an account from the repository. The aggregate is deleted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function remove(Account $account): void;

    /**
     * Check if an email already exists in the repository.
     */
    public function existsByEmail(Email $email): bool;
}
