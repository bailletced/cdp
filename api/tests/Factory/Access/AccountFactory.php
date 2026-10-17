<?php

declare(strict_types=1);

namespace App\Tests\Factory\Access;

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Uid\Uuid;

/**
 * Factory for creating Account instances for testing.
 * Use this in integration and functional tests where you need real Account objects.
 */
final class AccountFactory
{
    public static function create(
        ?Uuid $id = null,
        ?string $email = null,
        int $credentialsVersion = 1,
        ?string $priantId = null
    ): Account {
        $id = $id ?? Uuid::v7();
        $email = $email ?? self::faker()->safeEmail();

        return new Account(
            new AccountId($id),
            new Email($email),
            $credentialsVersion,
            $priantId
        );
    }

    public static function createOrganizer(
        ?Uuid $id = null,
        ?string $email = null
    ): Account {
        return self::create($id, $email, 1, null);
    }

    private static function faker(): \Faker\Generator
    {
        return \Faker\Factory::create();
    }
}
