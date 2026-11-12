<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Repository;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\Doctrine\DoctrineSlotTextsDeliveries;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineSlotTextsSentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSlotTextsSentRepositoryTest extends KernelTestCase
{
    private DoctrineSlotTextsSentRepository $repository;

    private DoctrineSlotTextsDeliveries $deliveries;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        $this->repository = new DoctrineSlotTextsSentRepository($doctrine);
        $this->em = $doctrine->getManager();
        $this->deliveries = new DoctrineSlotTextsDeliveries($this->repository, $this->em);

        // Clean up test data before each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE slot_texts_sent CASCADE');
        $this->em->clear();
    }

    public function testThePresenceOfTheRowTellsTheDeliveryApart(): void
    {
        $priantId = new PriantId();
        $editionId = new EditionId();

        self::assertFalse($this->deliveries->alreadySentTo($priantId, $editionId));

        $this->deliveries->recordDelivery($priantId, $editionId, new \DateTimeImmutable('2026-11-10 19:00:00 UTC'));

        self::assertTrue($this->deliveries->alreadySentTo($priantId, $editionId));

        $delivery = $this->repository->findByPriantAndEdition($priantId, $editionId);
        self::assertNotNull($delivery);
        self::assertEquals(new \DateTimeImmutable('2026-11-10 19:00:00 UTC'), $delivery->getSentAt());
    }

    public function testAnotherEditionOrPriantIsNotDelivered(): void
    {
        $this->deliveries->recordDelivery(new PriantId(), new EditionId(), new \DateTimeImmutable('2026-11-10 19:00:00 UTC'));

        self::assertFalse($this->deliveries->alreadySentTo(new PriantId(), new EditionId()));
    }

    public function testADeliveryIsFlushedAsSoonAsItIsRecorded(): void
    {
        $this->deliveries->recordDelivery(new PriantId(), new EditionId(), new \DateTimeImmutable('2026-11-10 19:00:00 UTC'));

        $count = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM slot_texts_sent');

        self::assertSame(1, $count);
    }
}
