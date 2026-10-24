<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain\Repository;

use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\DevicePassRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Shared repository contract (in-memory unit test and Doctrine integration
 * test). The base is a KernelTestCase so the Doctrine subclass can boot the
 * kernel from getRepository(); the in-memory subclass never boots it.
 */
abstract class DevicePassRepositoryTestCase extends KernelTestCase
{
    protected DevicePassRepositoryInterface $repository;

    abstract protected function getRepository(): DevicePassRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    /**
     * Hook for Doctrine-backed subclasses: persist must reach the database
     * before find* is called. No-op for in-memory repositories.
     */
    protected function flush(): void
    {
    }

    public function testAPassIsFoundByItsToken(): void
    {
        $inscriptionId = Uuid::v7()->toRfc4122();
        $pass = new DevicePass('device-token', [$inscriptionId], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00'));

        $this->repository->add($pass);
        $this->flush();

        $found = $this->repository->findByToken('device-token');
        self::assertNotNull($found);
        self::assertTrue($pass->getId()->equals($found->getId()));
        self::assertSame([$inscriptionId], $found->getInscriptionIds());
    }

    public function testAnUnknownTokenFindsNothing(): void
    {
        $this->repository->add(new DevicePass('device-token', [], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00')));
        $this->flush();

        self::assertNull($this->repository->findByToken('another-token'));
        // The stored hash is no token: looking it up as one finds nothing
        self::assertNull($this->repository->findByToken(DevicePass::hashToken('device-token')));
    }

    public function testCoveredInscriptionsAreKept(): void
    {
        $pass = new DevicePass('device-token', [Uuid::v7()->toRfc4122()], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00'));
        $this->repository->add($pass);
        $this->flush();

        $this->repository->findByToken('device-token')?->cover([Uuid::v7()->toRfc4122()], new \DateTimeImmutable('2026-12-06 20:00'));
        $this->flush();

        self::assertCount(2, $this->repository->findByToken('device-token')?->getInscriptionIds() ?? []);
    }
}
