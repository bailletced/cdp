<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine\Repository;

use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\DevicePassRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template-extends ServiceEntityRepository<DevicePass>
 */
final class DoctrineDevicePassRepository extends ServiceEntityRepository implements DevicePassRepositoryInterface
{
    public function __construct(Registry $registry, string $entityClass = DevicePass::class)
    {
        parent::__construct($registry, $entityClass);
    }

    #[\Override]
    public function add(DevicePass $pass): void
    {
        $this->getEntityManager()->persist($pass);
    }

    #[\Override]
    public function findByToken(#[\SensitiveParameter] string $token): ?DevicePass
    {
        return $this->findOneBy(['tokenHash' => DevicePass::hashToken($token)]);
    }
}
