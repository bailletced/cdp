<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain\Repository;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test case for AccountRepository implementations.
 * Concrete implementations should extend this class and implement getRepository().
 */
abstract class AccountRepositoryTestCase extends TestCase
{
    protected AccountRepositoryInterface $repository;

    /**
     * Get the repository instance to test.
     */
    abstract protected function getRepository(): AccountRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    public function testAddAndFindById(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account);

        $found = $this->repository->findById($account->getId());
        $this->assertNotNull($found);
        $this->assertSame($account, $found);
    }

    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $found = $this->repository->findById(new AccountId());
        $this->assertNull($found);
    }

    public function testAddAndFindByEmail(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account);

        $found = $this->repository->findByEmail(new Email('test@bailletced.org'));
        $this->assertNotNull($found);
        $this->assertSame($account, $found);
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account);

        $found = $this->repository->findByEmail(new Email('TEST@bailletced.org'));
        $this->assertNotNull($found);
        $this->assertSame($account, $found);
    }

    public function testFindByEmailReturnsNullForNonExistentEmail(): void
    {
        $found = $this->repository->findByEmail(new Email('nonexistent@bailletced.org'));
        $this->assertNull($found);
    }

    public function testExistsByEmail(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account);

        $this->assertTrue($this->repository->existsByEmail(new Email('test@bailletced.org')));
        $this->assertTrue($this->repository->existsByEmail(new Email('TEST@bailletced.org')));
        $this->assertFalse($this->repository->existsByEmail(new Email('other@bailletced.org')));
    }

    public function testRemove(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account);
        $this->repository->remove($account);

        $found = $this->repository->findById($account->getId());
        $this->assertNull($found);

        $foundByEmail = $this->repository->findByEmail(new Email('test@bailletced.org'));
        $this->assertNull($foundByEmail);
    }

    public function testUpdateAccount(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account);

        // Update credentials version
        $account->incrementCredentialsVersion();
        $this->repository->add($account);

        $found = $this->repository->findById($account->getId());
        $this->assertNotNull($found);
        $this->assertEquals(2, $found->getCredentialsVersion());
    }

    public function testAddMultipleAccounts(): void
    {
        $account1 = new Account(
            new AccountId(),
            new Email('test1@bailletced.org'),
            1,
            null
        );

        $account2 = new Account(
            new AccountId(),
            new Email('test2@bailletced.org'),
            1,
            null
        );

        $this->repository->add($account1);
        $this->repository->add($account2);

        $found1 = $this->repository->findById($account1->getId());
        $found2 = $this->repository->findById($account2->getId());

        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertSame($account1, $found1);
        $this->assertSame($account2, $found2);
    }

    public function testAccountWithPriantId(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@bailletced.org'),
            1,
            '123e4567-e89b-12d3-a456-426614174000'
        );

        $this->repository->add($account);

        $found = $this->repository->findById($account->getId());
        $this->assertNotNull($found);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $found->getPriantId());

        $account->updatePriantId('00000000-0000-0000-0000-000000000000');
        $this->repository->add($account);

        $found = $this->repository->findById($account->getId());
        $this->assertNotNull($found);
        $this->assertEquals('00000000-0000-0000-0000-000000000000', $found->getPriantId());
    }
}
