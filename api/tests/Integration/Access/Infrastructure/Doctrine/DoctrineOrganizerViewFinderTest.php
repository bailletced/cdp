<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Doctrine;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Entity\Organizer;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Doctrine\DoctrineOrganizerViewFinder;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrganizerViewFinderTest extends KernelTestCase
{
    private DoctrineOrganizerViewFinder $finder;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->finder = new DoctrineOrganizerViewFinder($this->em);

        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE organizer CASCADE');
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }

    public function testFindAllReturnsOrganizersWithEmails(): void
    {
        $adder = $this->createAccount('adder@bailletced.org');
        $organizerAccount = $this->createAccount('anne@bailletced.org');
        $this->createOrganizer($organizerAccount, $adder);

        $results = $this->finder->findAll();

        self::assertCount(1, $results);
        self::assertSame('anne@bailletced.org', $results[0]->email);
        self::assertSame('adder@bailletced.org', $results[0]->addedByEmail);
        self::assertNotEmpty($results[0]->id);
        self::assertNotEmpty($results[0]->addedAt);
    }

    public function testFindAllReturnsEmptyArrayWhenNoOrganizers(): void
    {
        self::assertSame([], $this->finder->findAll());
    }

    public function testFindAllReturnsMultipleOrganizers(): void
    {
        $adder = $this->createAccount('adder@bailletced.org');
        $anne = $this->createAccount('anne@bailletced.org');
        $bob = $this->createAccount('bob@bailletced.org');

        $this->createOrganizer($anne, $adder);
        $this->createOrganizer($bob, $adder);

        $results = $this->finder->findAll();

        self::assertCount(2, $results);
    }

    public function testFindByIdReturnsTheOrganizer(): void
    {
        $adder = $this->createAccount('adder@bailletced.org');
        $organizerAccount = $this->createAccount('anne@bailletced.org');
        $organizer = $this->createOrganizer($organizerAccount, $adder);

        $found = $this->finder->findById((string) $organizer->getId());

        self::assertNotNull($found);
        self::assertSame('anne@bailletced.org', $found->email);
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->finder->findById(Uuid::v7()->toRfc4122()));
    }

    public function testFindByEmailReturnsTheOrganizer(): void
    {
        $adder = $this->createAccount('adder@bailletced.org');
        $organizerAccount = $this->createAccount('anne@bailletced.org');
        $this->createOrganizer($organizerAccount, $adder);

        $found = $this->finder->findByEmail(new Email('anne@bailletced.org'));

        self::assertNotNull($found);
        self::assertSame('anne@bailletced.org', $found->email);
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        $adder = $this->createAccount('adder@bailletced.org');
        $organizerAccount = $this->createAccount('anne@bailletced.org');
        $this->createOrganizer($organizerAccount, $adder);

        $found = $this->finder->findByEmail(new Email('ANNE@bailletced.org'));

        self::assertNotNull($found);
    }

    public function testFindByEmailReturnsNullForNonOrganizer(): void
    {
        $this->createAccount('priant@bailletced.org');

        self::assertNull($this->finder->findByEmail(new Email('priant@bailletced.org')));
    }

    private function createAccount(string $email): Account
    {
        $account = new Account(new AccountId(), new Email($email));
        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

    private function createOrganizer(Account $account, Account $addedBy): Organizer
    {
        $organizer = new Organizer($account->getId()->value, $addedBy->getId()->value);
        $this->em->persist($organizer);
        $this->em->flush();

        return $organizer;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE organizer CASCADE');
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }
}
