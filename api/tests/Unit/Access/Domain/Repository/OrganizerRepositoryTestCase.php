<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain\Repository;

use App\Access\Domain\Organizer;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Abstract test case for OrganizerRepository implementations.
 * Concrete implementations should extend this class and implement getRepository().
 */
abstract class OrganizerRepositoryTestCase extends TestCase
{
    protected OrganizerRepositoryInterface $repository;

    /**
     * Get the repository instance to test.
     */
    abstract protected function getRepository(): OrganizerRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    public function testSaveAndFindByAccountId(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();
        $organizer = new Organizer($accountId, $addedBy);

        $this->repository->save($organizer);

        $found = $this->repository->findByAccountId($accountId);
        $this->assertNotNull($found);
        $this->assertSame($organizer, $found);
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
        $organizer = new Organizer($accountId, $addedBy);

        $this->repository->save($organizer);

        $this->assertTrue($this->repository->isOrganizer($accountId));
        $this->assertFalse($this->repository->isOrganizer(Uuid::v7()));
    }

    public function testFindAll(): void
    {
        $accountId1 = Uuid::v7();
        $accountId2 = Uuid::v7();
        $addedBy = Uuid::v7();

        $organizer1 = new Organizer($accountId1, $addedBy);
        $organizer2 = new Organizer($accountId2, $addedBy);

        $this->repository->save($organizer1);
        $this->repository->save($organizer2);

        $all = $this->repository->findAll();
        $this->assertCount(2, $all);
        $this->assertContains($organizer1, $all);
        $this->assertContains($organizer2, $all);
    }

    public function testCountAll(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();
        $organizer = new Organizer($accountId, $addedBy);

        $this->assertEquals(0, $this->repository->countAll());

        $this->repository->save($organizer);
        $this->assertEquals(1, $this->repository->countAll());

        $accountId2 = Uuid::v7();
        $organizer2 = new Organizer($accountId2, $addedBy);
        $this->repository->save($organizer2);
        $this->assertEquals(2, $this->repository->countAll());
    }

    public function testRemove(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();
        $organizer = new Organizer($accountId, $addedBy);

        $this->repository->save($organizer);
        $this->assertEquals(1, $this->repository->countAll());

        $this->repository->remove($organizer);
        $this->assertEquals(0, $this->repository->countAll());

        $found = $this->repository->findByAccountId($accountId);
        $this->assertNull($found);
    }

    public function testCannotAddSameAccountTwice(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();
        $organizer1 = new Organizer($accountId, $addedBy);
        $organizer2 = new Organizer($accountId, $addedBy);

        $this->repository->save($organizer1);
        $this->repository->save($organizer2);

        $all = $this->repository->findAll();
        $this->assertCount(1, $all);
        
        $found = $this->repository->findByAccountId($accountId);
        $this->assertNotNull($found);
        $this->assertSame($organizer2, $found);
    }
}
