<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine\Repository;

use App\Access\Domain\Entity\Organizer;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @template-extends ServiceEntityRepository<Organizer>
 *
 * @method Organizer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organizer|null findOneBy(array<string, mixed> $criteria)
 * @method Organizer[]    findAll()
 * @method Organizer[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
final class DoctrineOrganizerRepository extends ServiceEntityRepository implements OrganizerRepositoryInterface
{
    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $registry, string $entityClass = Organizer::class)
    {
        parent::__construct($registry, $entityClass);
    }

    #[\Override]
    public function findByAccountId(Uuid $accountId): ?Organizer
    {
        return $this->findOneBy(['accountId' => $accountId->toRfc4122()]);
    }

    #[\Override]
    public function isOrganizer(Uuid $accountId): bool
    {
        return null !== $this->findByAccountId($accountId);
    }

    #[\Override]
    public function add(Organizer $organizer): void
    {
        $this->getEntityManager()->persist($organizer);
    }

    #[\Override]
    public function remove(Organizer $organizer): void
    {
        $this->getEntityManager()->remove($organizer);
    }

    #[\Override]
    public function countAll(): int
    {
        return $this->count([]);
    }
}
