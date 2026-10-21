<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

abstract class EditionRepositoryTestCase extends TestCase
{
    protected EditionRepositoryInterface $repository;

    abstract protected function getRepository(): EditionRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    public function testAddAndFind(): void
    {
        $edition = $this->createEdition();

        $this->repository->add($edition);

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

        $all = $this->repository->findAll();
        self::assertCount(2, $all);
    }

    public function testRemove(): void
    {
        $edition = $this->createEdition();

        $this->repository->add($edition);
        self::assertNotNull($this->repository->findById($edition->getId()));

        $this->repository->remove($edition);
        self::assertNull($this->repository->findById($edition->getId()));
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
