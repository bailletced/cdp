<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Repository;

use App\Access\Domain\Organizer;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineOrganizerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrganizerRepositoryTest extends KernelTestCase
{
    private DoctrineOrganizerRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        $this->repository = new DoctrineOrganizerRepository($doctrine);
        $this->em = $doctrine->getManager();

        // Clean up test data before each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE organizer CASCADE');
        $this->em->clear();
    }

    public function testAddAndFindByAccountId(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();

        $organizer = new Organizer($accountId, $addedBy);
        $this->repository->add($organizer);
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findByAccountId($accountId);
        $this->assertNotNull($found);
        $this->assertEquals($accountId, $found->getAccountId());
    }

    public function testFindByAccountIdReturnsNullForNonExistent(): void
    {
        $found = $this->repository->findByAccountId(Uuid::v7());
        $this->assertNull($found);
    }

    public function testIsOrganizer(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();
        $otherAccountId = Uuid::v7();

        $organizer = new Organizer($accountId, $addedBy);
        $this->repository->add($organizer);
        $this->em->flush();
        $this->em->clear();

        $this->assertTrue($this->repository->isOrganizer($accountId));
        $this->assertFalse($this->repository->isOrganizer($otherAccountId));
    }

    public function testFindAll(): void
    {
        $accountId1 = Uuid::v7();
        $accountId2 = Uuid::v7();
        $addedBy = Uuid::v7();

        $organizer1 = new Organizer($accountId1, $addedBy);
        $organizer2 = new Organizer($accountId2, $addedBy);

        $this->repository->add($organizer1);
        $this->repository->add($organizer2);
        $this->em->flush();
        $this->em->clear();

        $all = $this->repository->findAll();
        $this->assertCount(2, $all);
    }

    public function testCountAll(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();

        $this->assertEquals(0, $this->repository->countAll());

        $organizer = new Organizer($accountId, $addedBy);
        $this->repository->add($organizer);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals(1, $this->repository->countAll());
    }

    public function testRemove(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();

        $organizer = new Organizer($accountId, $addedBy);
        $this->repository->add($organizer);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals(1, $this->repository->countAll());

        // Find the organizer again to get a managed instance
        $organizerToRemove = $this->repository->findByAccountId($accountId);
        $this->assertNotNull($organizerToRemove);

        $this->repository->remove($organizerToRemove);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals(0, $this->repository->countAll());

        $found = $this->repository->findByAccountId($accountId);
        $this->assertNull($found);
    }

    public function testAddDoesNotFlush(): void
    {
        $organizer = new Organizer(Uuid::v7(), Uuid::v7());

        $this->repository->add($organizer);

        // The repository only registers the aggregate: nothing reaches the database on its own.
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM organizer'));

        $this->em->flush();

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM organizer'));
    }

    public function testRemoveDoesNotFlush(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();

        $organizer = new Organizer($accountId, $addedBy);
        $this->repository->add($organizer);
        $this->em->flush();
        $this->em->clear();

        $managed = $this->repository->findByAccountId($accountId);
        $this->assertNotNull($managed);

        $this->repository->remove($managed);

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM organizer'));

        $this->em->flush();

        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM organizer'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE organizer CASCADE');
        $this->em->clear();
    }
}
