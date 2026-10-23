<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine\Repository;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @template-extends ServiceEntityRepository<Priant>
 */
final class DoctrinePriantRepository extends ServiceEntityRepository implements PriantRepositoryInterface
{
    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $registry, string $entityClass = Priant::class)
    {
        parent::__construct($registry, $entityClass);
    }

    #[\Override]
    public function findById(PriantId $id): ?Priant
    {
        return $this->findOneBy(['id.value' => $id->value->toRfc4122()]);
    }

    #[\Override]
    public function findByEmail(Email $email): ?Priant
    {
        try {
            /** @var Priant|null $priant */
            $priant = $this->createQueryBuilder('p')
                ->andWhere('lower(p.email) = lower (:email)')
                ->setParameter('email', $email->value)
                ->getQuery()
                ->getOneOrNullResult();

            return $priant;
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    #[\Override]
    public function add(Priant $priant): void
    {
        $this->getEntityManager()->persist($priant);
    }
}
