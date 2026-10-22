<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template-extends ServiceEntityRepository<Edition>
 *
 * @method Edition|null find($id, $lockMode = null, $lockVersion = null)
 * @method Edition|null findOneBy(array<string, mixed> $criteria)
 * @method Edition[]    findAll()
 */
final class DoctrineEditionRepository extends ServiceEntityRepository implements EditionRepositoryInterface
{
    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $registry, string $entityClass = Edition::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function findById(EditionId $id): ?Edition
    {
        return $this->findOneBy(['id.value' => $id->value->toRfc4122()]);
    }

    public function findBySlotId(SlotId $slotId): ?Edition
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.slots', 's')
            ->where('s.id.value = :slotId')
            ->setParameter('slotId', $slotId->value->toRfc4122())
            ->setMaxResults(1);

        /** @var Edition|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function add(Edition $edition): void
    {
        $this->getEntityManager()->persist($edition);
    }

    public function remove(Edition $edition): void
    {
        $this->getEntityManager()->remove($edition);
    }
}
