<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Domain\Time\ParisCalendar;
use App\Tests\Acceptance\ViewerProfiles;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * DELETE /api/inscriptions/{id} (F14-T06, US-14.3): only the priant of the
 * inscription, in session, until its slot starts (RG-EP-1, voter
 * INSCRIPTION_ACT). A device pass only reads (RG-IN-6).
 */
final class CancelInscriptionApiTest extends ApiTestCase
{
    use ViewerProfiles;

    /**
     * Cancelling Marie's inscription, per profile.
     */
    private const array EXPECTED = [
        'visiteur' => 401,
        'pass' => 401,
        'priant vérifié' => 204,
        'autre priant' => 403,
        'organisateur' => 403,
    ];

    private KernelBrowser $client;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('HTTPS', 'on');

        $this->em = self::getContainer()->get('doctrine')->getManager();
        $connection = $this->em->getConnection();
        foreach (['device_pass', 'inscription', 'priant', 'slot', 'edition'] as $table) {
            $connection->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
    }

    #[DataProvider('viewerProfiles')]
    public function testOnlyThePriantOfTheInscriptionCancelsIt(string $profile): void
    {
        $this->givenTheProfilesOf($this->createEdition('2099-01-01 19:00:00'));
        $this->actAs($profile);

        $this->delete((string) $this->mariesInscription->getId());

        self::assertSame(self::EXPECTED[$profile], $this->client->getResponse()->getStatusCode());
    }

    public function testTheFreedSlotIsEmptyAgainInTheCurrentEdition(): void
    {
        $this->givenTheProfilesOf($this->createEdition('2099-01-01 19:00:00'));
        $this->actAs('priant vérifié');
        self::assertSame(47, $this->emptySlotCount());

        $this->delete((string) $this->mariesInscription->getId());

        self::assertSame(204, $this->client->getResponse()->getStatusCode());
        self::assertSame(48, $this->emptySlotCount());
    }

    public function testOnceTheSlotHasStartedTheInscriptionStays(): void
    {
        $current = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($current);
        $past = $this->createEdition('2025-11-28 19:00:00');
        $started = new Inscription($past->getId(), $past->getSlots()[0]->getId(), $this->mariesInscription->getPriantId());
        $this->em->persist($started);
        $this->em->flush();
        $this->actAs('priant vérifié');

        $this->delete((string) $started->getId());

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('déjà commencé', (string) $this->client->getResponse()->getContent());
    }

    public function testAnUnknownInscriptionIsNotFound(): void
    {
        $this->givenTheProfilesOf($this->createEdition('2099-01-01 19:00:00'));
        $this->actAs('priant vérifié');

        $this->delete('01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());

        $this->delete('nope');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    private function delete(string $inscriptionId): void
    {
        $this->client->request('DELETE', \sprintf('/api/inscriptions/%s', $inscriptionId), [], [], [
            'HTTP_ORIGIN' => 'https://localhost',
        ]);
    }

    private function emptySlotCount(): int
    {
        $this->client->request('GET', '/api/editions/current');

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['emptySlotCount'];
    }

    private function createEdition(string $startsAtUtc): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable($startsAtUtc, new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }
}
