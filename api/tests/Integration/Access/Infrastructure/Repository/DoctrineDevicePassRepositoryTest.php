<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Repository;

use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\DevicePassRepositoryInterface;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineDevicePassRepository;
use App\Tests\Unit\Access\Domain\Repository\DevicePassRepositoryTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineDevicePassRepositoryTest extends DevicePassRepositoryTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function getRepository(): DevicePassRepositoryInterface
    {
        self::bootKernel();

        $registry = self::getContainer()->get('doctrine');
        $this->em = $registry->getManager();
        $repository = new DoctrineDevicePassRepository($registry, DevicePass::class);

        $this->em->getConnection()->executeStatement('DELETE FROM device_pass');
        $this->em->clear();

        return $repository;
    }

    #[\Override]
    protected function flush(): void
    {
        $this->em->flush();
        $this->em->clear();
    }

    public function testAddDoesNotFlush(): void
    {
        $this->repository->add(new DevicePass('device-token', [], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00')));

        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM device_pass'));
    }

    public function testTheTokenIsNeverStoredInClear(): void
    {
        $this->repository->add(new DevicePass('device-token', [], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00')));
        $this->flush();

        $row = $this->em->getConnection()->fetchAssociative('SELECT * FROM device_pass');
        self::assertIsArray($row);
        self::assertSame(DevicePass::hashToken('device-token'), $row['token_hash']);
        self::assertStringNotContainsString('device-token', (string) json_encode($row));
    }
}
