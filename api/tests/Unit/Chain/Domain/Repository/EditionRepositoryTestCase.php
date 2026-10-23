<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Domain\Time\ParisCalendar;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Shared repository contract (in-memory unit test and Doctrine integration
 * test). The base is a KernelTestCase so the Doctrine subclass can boot the
 * kernel from getRepository(); the in-memory subclass never boots it.
 */
abstract class EditionRepositoryTestCase extends KernelTestCase
{
    protected EditionRepositoryInterface $repository;

    abstract protected function getRepository(): EditionRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    /**
     * Hook for Doctrine-backed subclasses: persist (and remove) must reach
     * the database before find* is called. No-op for in-memory repositories.
     */
    protected function flush(): void
    {
    }

    public function testAddAndFind(): void
    {
        $edition = $this->createEdition();

        $this->repository->add($edition);
        $this->flush();

        $found = $this->repository->findById($edition->getId());
        self::assertNotNull($found);
        self::assertSame($edition, $found);
    }

    public function testFindReturnsNullForUnknownId(): void
    {
        $found = $this->repository->findById(new \App\Chain\Domain\ValueObject\EditionId());
        self::assertNull($found);
    }

    public function testFindAll(): void
    {
        $edition1 = $this->createEdition();
        $edition2 = $this->createEdition();

        $this->repository->add($edition1);
        $this->repository->add($edition2);
        $this->flush();

        $all = $this->repository->findAll();
        self::assertCount(2, $all);
    }

    public function testRemove(): void
    {
        $edition = $this->createEdition();

        $this->repository->add($edition);
        $this->flush();
        self::assertNotNull($this->repository->findById($edition->getId()));

        $this->repository->remove($edition);
        $this->flush();
        self::assertNull($this->repository->findById($edition->getId()));
    }

    public function testRescheduledEditionIsSavedWithItsMovedSlots(): void
    {
        $edition = $this->createEdition();
        $this->repository->add($edition);
        $this->flush();

        // Moving the start must update the 48 slots in place: recreating them
        // would insert the new ranks before deleting the old ones and break
        // the (edition_id, rank) unique index.
        $edition->reschedule(
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-14 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
            hasInscriptions: false,
        );
        $this->flush();

        $found = $this->repository->findById($edition->getId());
        self::assertNotNull($found);
        self::assertCount(48, $found->getSlots());
        self::assertSame('2026-11-28 20:00:00', $found->getSlots()[0]->getStartsAt()->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-14 19:00:00', $found->getOpensAt()->format('Y-m-d H:i:s'));
    }

    private function createEdition(): Edition
    {
        return Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
    }
}
