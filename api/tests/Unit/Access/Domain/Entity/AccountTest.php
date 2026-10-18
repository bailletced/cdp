<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain\Entity;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function testCreateAccountWithDefaultCredentialsVersion(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com')
        );

        $this->assertEquals(1, $account->getCredentialsVersion());
    }

    public function testCreateAccountWithCustomCredentialsVersion(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            5
        );

        $this->assertEquals(5, $account->getCredentialsVersion());
    }

    public function testIncrementCredentialsVersion(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1
        );

        $account->incrementCredentialsVersion();
        $this->assertEquals(2, $account->getCredentialsVersion());

        $account->incrementCredentialsVersion();
        $this->assertEquals(3, $account->getCredentialsVersion());
    }

    public function testGetPriantId(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            '123e4567-e89b-12d3-a456-426614174000'
        );

        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $account->getPriantId());
    }

    public function testGetPriantIdNull(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com')
        );

        $this->assertNull($account->getPriantId());
    }

    public function testUpdatePriantId(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com')
        );

        $account->updatePriantId('123e4567-e89b-12d3-a456-426614174000');
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $account->getPriantId());

        $account->updatePriantId(null);
        $this->assertNull($account->getPriantId());
    }

    public function testGetId(): void
    {
        $accountId = new AccountId();
        $account = new Account(
            $accountId,
            new Email('test@example.com')
        );

        $this->assertSame($accountId, $account->getId());
    }

    public function testGetEmail(): void
    {
        $email = new Email('test@example.com');
        $account = new Account(
            new AccountId(),
            $email
        );

        $this->assertSame($email, $account->getEmail());
    }
}
