<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Port\SlotTextsDeliveries;
use App\Chain\Domain\Entity\SlotTextsSent;
use App\Chain\Domain\Repository\SlotTextsSentRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine implementation of SlotTextsDeliveries (F24): a delivery exists as
 * long as its row does. Unlike the repositories, the delivery is flushed
 * here: the console command records each e-mail right after sending it, so
 * a crash never loses what was already delivered.
 */
final readonly class DoctrineSlotTextsDeliveries implements SlotTextsDeliveries
{
    public function __construct(
        private SlotTextsSentRepositoryInterface $deliveries,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[\Override]
    public function alreadySentTo(PriantId $priantId, EditionId $editionId): bool
    {
        return null !== $this->deliveries->findByPriantAndEdition($priantId, $editionId);
    }

    #[\Override]
    public function recordDelivery(PriantId $priantId, EditionId $editionId, \DateTimeImmutable $at): void
    {
        $this->deliveries->add(SlotTextsSent::record($priantId, $editionId, $at));
        $this->entityManager->flush();
    }
}
