<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Repository;

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineAccountRepository;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineAccountRepositoryTest extends KernelTestCase
{
    private DoctrineAccountRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $doctrine = $container->get('doctrine');
        
        $this->repository = new DoctrineAccountRepository($doctrine);
        $this->em = $doctrine->getManager();

        // Clean up test data before each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }

    public function testSaveAndFindById(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            null
        );

        $this->repository->save($account);
        $this->em->clear();

        $found = $this->repository->findById($account->getId());
        $this->assertNotNull($found);
        $this->assertEquals($account->getId(), $found->getId());
    }

    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $found = $this->repository->findById(new AccountId());
        $this->assertNull($found);
    }

    public function testSaveAndFindByEmail(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            null
        );

        $this->repository->save($account);
        $this->em->clear();

        $found = $this->repository->findByEmail(new Email('test@example.com'));
        $this->assertNotNull($found);
        $this->assertEquals($account->getId(), $found->getId());
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            null
        );

        $this->repository->save($account);
        $this->em->clear();

        $found = $this->repository->findByEmail(new Email('TEST@EXAMPLE.COM'));
        $this->assertNotNull($found);
        $this->assertEquals($account->getId(), $found->getId());
    }

    public function testExistsByEmail(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            null
        );

        $this->repository->save($account);
        $this->em->clear();

        $this->assertTrue($this->repository->existsByEmail(new Email('test@example.com')));
        $this->assertTrue($this->repository->existsByEmail(new Email('TEST@EXAMPLE.COM')));
        $this->assertFalse($this->repository->existsByEmail(new Email('other@example.com')));
    }

    public function testRemove(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            null
        );

        $this->repository->save($account);
        $this->repository->remove($account);
        $this->em->clear();

        $found = $this->repository->findById($account->getId());
        $this->assertNull($found);

        $foundByEmail = $this->repository->findByEmail(new Email('test@example.com'));
        $this->assertNull($foundByEmail);
    }

    public function testUpdateAccount(): void
    {
        $account = new Account(
            new AccountId(),
            new Email('test@example.com'),
            1,
            null
        );

        $this->repository->save($account);

        // Update credentials version
        $account->incrementCredentialsVersion();
        $this->repository->save($account);
        $this->em->clear();

        $found = $this->repository->findById($account->getId());
        $this->assertNotNull($found);
        $this->assertEquals(2, $found->getCredentialsVersion());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }
}
