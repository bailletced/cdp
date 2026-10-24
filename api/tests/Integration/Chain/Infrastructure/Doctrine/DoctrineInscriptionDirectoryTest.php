<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\Doctrine\DoctrineInscriptionDirectory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class DoctrineInscriptionDirectoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DoctrineInscriptionDirectory $directory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->directory = new DoctrineInscriptionDirectory($this->em->getConnection());

        $this->em->getConnection()->executeStatement('DELETE FROM inscription');
    }

    public function testOnlyTheInscriptionsStillInPlaceAreActive(): void
    {
        $kept = new Inscription(new EditionId(), new SlotId(), new PriantId());
        $this->em->persist($kept);
        $this->em->flush();
        $cancelled = Uuid::v7()->toRfc4122();

        $active = $this->directory->filterActive([$kept->getId()->value->toRfc4122(), $cancelled]);

        self::assertSame([$kept->getId()->value->toRfc4122()], $active);
    }

    public function testNoIdNoQuery(): void
    {
        self::assertSame([], $this->directory->filterActive([]));
    }

    public function testAMalformedIdIsNeverActive(): void
    {
        self::assertSame([], $this->directory->filterActive(['not-a-uuid']));
    }
}
