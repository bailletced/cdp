<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine\Repository;

use App\Access\Domain\Organizer;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @template-extends ServiceEntityRepository<Organizer>
 *
 * @method Organizer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organizer|null findOneBy(array $criteria)
 * @method Organizer[]    findAll()
 * @method Organizer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class DoctrineOrganizerRepository extends ServiceEntityRepository implements OrganizerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organizer::class);
    }

    #[\Override]
    public function findByAccountId(Uuid $accountId): ?Organizer
    {
        return $this->findOneBy(['accountId' => $accountId->toRfc4122()]);
    }

    #[\Override]
    public function isOrganizer(Uuid $accountId): bool
    {
        return $this->findByAccountId($accountId) !== null;
    }

    #[\Override]
    public function save(Organizer $organizer): void
    {
        $em = $this->getEntityManager();
        $em->persist($organizer);
        $em->flush();
    }

    #[\Override]
    public function remove(Organizer $organizer): void
    {
        $em = $this->getEntityManager();
        $em->remove($organizer);
        $em->flush();
    }

    #[\Override]
    public function countAll(): int
    {
        return $this->count([]);
    }
}
