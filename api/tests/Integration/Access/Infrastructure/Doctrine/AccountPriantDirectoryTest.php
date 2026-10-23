<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Doctrine;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Doctrine\AccountPriantDirectory;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineAccountRepository;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccountPriantDirectoryTest extends KernelTestCase
{
    private const string PRIANT_ID = '018f3a2e-7c1d-7a2b-9c4d-5e6f7a8b9c0d';

    private AccountPriantDirectory $directory;

    private DoctrineAccountRepository $accountRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        $this->em = $doctrine->getManager();
        $this->accountRepository = new DoctrineAccountRepository($doctrine);
        $this->directory = new AccountPriantDirectory($this->accountRepository, $this->em);

        // Clean up test data before each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }

    public function testItCreatesAnAccountWhenNoneExists(): void
    {
        $email = new Email('famille.martin@bailletced.org');

        $this->directory->linkAccountToPriant($email, self::PRIANT_ID);

        // The adapter flushes explicitly (event bus has no transaction).
        $account = $this->accountRepository->findByEmail($email);
        self::assertNotNull($account);
        self::assertSame(self::PRIANT_ID, $account->getPriantId());
        self::assertSame(1, $account->getCredentialsVersion());
    }

    public function testItLinksAnExistingAccount(): void
    {
        // An organizer already has an account (F02) and registers on the site
        // with the same email: the existing account is linked, not duplicated.
        $account = new Account(new AccountId(), new Email('marie@bailletced.org'));
        $this->accountRepository->add($account);
        $this->em->flush();
        $this->em->clear();

        $this->directory->linkAccountToPriant(new Email('marie@bailletced.org'), self::PRIANT_ID);

        $linked = $this->accountRepository->findByEmail(new Email('marie@bailletced.org'));
        self::assertNotNull($linked);
        self::assertEquals($account->getId(), $linked->getId());
        self::assertSame(self::PRIANT_ID, $linked->getPriantId());
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM account'));
    }

    public function testItMatchesTheEmailCaseInsensitively(): void
    {
        $this->accountRepository->add(new Account(new AccountId(), new Email('Marie@BailletCed.org')));
        $this->em->flush();
        $this->em->clear();

        $this->directory->linkAccountToPriant(new Email('marie@bailletced.org'), self::PRIANT_ID);

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM account'));
        $account = $this->accountRepository->findByEmail(new Email('MARIE@BAILLETCED.ORG'));
        self::assertNotNull($account);
        self::assertSame(self::PRIANT_ID, $account->getPriantId());
    }

    public function testItIsIdempotentWhenTheAccountIsAlreadyLinked(): void
    {
        $email = new Email('famille.martin@bailletced.org');

        $this->directory->linkAccountToPriant($email, self::PRIANT_ID);
        $this->directory->linkAccountToPriant($email, self::PRIANT_ID);

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM account'));
        $account = $this->accountRepository->findByEmail($email);
        self::assertNotNull($account);
        self::assertSame(self::PRIANT_ID, $account->getPriantId());
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
