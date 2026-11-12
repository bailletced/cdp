<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine\Repository;

use App\Chain\Domain\Entity\SlotTextsSent;
use App\Chain\Domain\Repository\SlotTextsSentRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template-extends ServiceEntityRepository<SlotTextsSent>
 */
final class DoctrineSlotTextsSentRepository extends ServiceEntityRepository implements SlotTextsSentRepositoryInterface
{
    public function __construct(Registry $registry, string $entityClass = SlotTextsSent::class)
    {
        parent::__construct($registry, $entityClass);
    }

    #[\Override]
    public function findByPriantAndEdition(PriantId $priantId, EditionId $editionId): ?SlotTextsSent
    {
        /** @var SlotTextsSent|null $delivery */
        $delivery = $this->findOneBy([
            'priantId' => $priantId->value,
            'editionId' => $editionId->value,
        ]);

        return $delivery;
    }

    #[\Override]
    public function add(SlotTextsSent $delivery): void
    {
        $this->getEntityManager()->persist($delivery);
    }
}
