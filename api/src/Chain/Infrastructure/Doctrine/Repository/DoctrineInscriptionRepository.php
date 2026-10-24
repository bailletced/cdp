<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine\Repository;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template-extends ServiceEntityRepository<Inscription>
 */
final class DoctrineInscriptionRepository extends ServiceEntityRepository implements InscriptionRepositoryInterface
{
    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $registry, string $entityClass = Inscription::class)
    {
        parent::__construct($registry, $entityClass);
    }

    #[\Override]
    public function add(Inscription $inscription): void
    {
        $this->getEntityManager()->persist($inscription);
    }

    #[\Override]
    public function countByEdition(EditionId $editionId): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id.value)')
            ->andWhere('i.editionId = :editionId')
            ->setParameter('editionId', $editionId->value->toRfc4122())
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function findOwnedOrCovered(?PriantId $priantId, array $inscriptionIds): array
    {
        $owned = null === $priantId ? [] : $this->findBy(['priantId' => $priantId->value->toRfc4122()]);
        $covered = [] === $inscriptionIds ? [] : $this->findBy(['id.value' => array_map(static fn (InscriptionId $id): string => $id->value->toRfc4122(), $inscriptionIds)]);

        // An inscription both owned and covered is listed once (the entity map gives the same instance)
        return array_values(array_unique([...$owned, ...$covered], \SORT_REGULAR));
    }

    #[\Override]
    public function findById(InscriptionId $id): ?Inscription
    {
        return $this->findOneBy(['id.value' => $id->value->toRfc4122()]);
    }

    #[\Override]
    public function remove(Inscription $inscription): void
    {
        $this->getEntityManager()->remove($inscription);
    }
}
