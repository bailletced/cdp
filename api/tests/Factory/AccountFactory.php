<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Account>
 */
final class AccountFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'id' => new AccountId(Uuid::v7()),
            'email' => new Email(self::faker()->unique()->safeEmail()),
            'credentialsVersion' => 1,
            'priantId' => null,
        ];
    }

    /**
     * @return class-string<Account>
     */
    public static function class(): string
    {
        return Account::class;
    }
}
