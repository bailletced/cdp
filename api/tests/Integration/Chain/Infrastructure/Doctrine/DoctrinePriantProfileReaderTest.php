<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Shared\Application\Dto\PriantProfile;
use App\Shared\Application\Port\PriantProfileReader;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Integration test for DoctrinePriantProfileReader (F09-T14): the profile
 * of a priant for the "me" endpoint — display name and consent state
 * (RG-IN-7 pre-fill), null when the priant does not exist.
 */
final class DoctrinePriantProfileReaderTest extends KernelTestCase
{
    private PriantProfileReader $reader;

    private PriantRepositoryInterface $priantRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->reader = $container->get(PriantProfileReader::class);
        $this->priantRepository = $container->get(PriantRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        $this->em->getConnection()->executeStatement('DELETE FROM priant');
        $this->em->clear();
    }

    public function testItReturnsNullForAnUnknownPriantId(): void
    {
        self::assertNull($this->reader->findProfileByPriantId(Uuid::v7()->toRfc4122()));
    }

    public function testItReturnsNullForAnInvalidPriantId(): void
    {
        self::assertNull($this->reader->findProfileByPriantId('not-a-uuid'));
    }

    public function testItExposesTheDisplayNameAndTheConsentState(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $this->priantRepository->add($priant);
        $this->em->flush();

        $profile = $this->reader->findProfileByPriantId($priant->getId()->value->toRfc4122());

        self::assertInstanceOf(PriantProfile::class, $profile);
        self::assertSame('Marie Dupont', $profile->displayName);
        self::assertFalse($profile->consentGiven);
    }

    public function testItReportsTheConsentAsGivenOnceRecorded(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $priant->giveConsent(new \DateTimeImmutable('2026-10-20 08:00:00', new \DateTimeZone('UTC')));
        $this->priantRepository->add($priant);
        $this->em->flush();

        $profile = $this->reader->findProfileByPriantId($priant->getId()->value->toRfc4122());

        self::assertNotNull($profile);
        self::assertTrue($profile->consentGiven);
    }
}
