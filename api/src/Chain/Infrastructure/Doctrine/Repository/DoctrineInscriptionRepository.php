<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine\Repository;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
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
}
