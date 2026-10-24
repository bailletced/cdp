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
 * GET /api/me/inscriptions?edition= (F14-T02, US-14.1): the slots of the
 * verified priant, of the public edition by default. A device pass only
 * reads the grid: « Mon espace » takes the session (RG-IN-6).
 */
final class MyInscriptionsApiTest extends ApiTestCase
{
    use ViewerProfiles;

    /**
     * HTTP status and number of slots, per profile.
     */
    private const array EXPECTED = [
        'visiteur' => [401, null],
        'pass' => [401, null],
        'priant vérifié' => [200, 1],
        'autre priant' => [200, 0],
        'organisateur' => [403, null],
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
    public function testOnlyAVerifiedPriantSeesItsSlots(string $profile): void
    {
        $this->givenTheProfilesOf($this->createEdition('2099-01-01 19:00:00'));
        $this->actAs($profile);

        $this->client->request('GET', '/api/me/inscriptions');

        $response = $this->client->getResponse();
        [$status, $count] = self::EXPECTED[$profile];
        self::assertSame($status, $response->getStatusCode());
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        if (null !== $count) {
            self::assertMatchesJsonSchema((string) $response->getContent(), 'my-inscriptions');
            self::assertCount($count, json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR)['inscriptions']);
        }
    }

    public function testMySpaceShowsMyNameAndMySlotsOfThePublicEdition(): void
    {
        $edition = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($edition);
        $this->actAs('priant vérifié');

        $this->client->request('GET', '/api/me/inscriptions');

        $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Marie Dupont', $body['displayName']);
        self::assertSame((string) $edition->getId(), $body['editionId']);
        self::assertSame('24 heures de prière', $body['editionTitle']);
        self::assertSame([
            'id' => (string) $this->mariesInscription->getId(),
            'slotId' => (string) $edition->getSlots()[0]->getId(),
            'startsAt' => '2099-01-01T19:00:00+00:00',
            'endsAt' => '2099-01-01T19:30:00+00:00',
            'label' => '20h00–20h30',
            'dayLabel' => 'jeudi 1 janvier',
            'textTitle' => null,
            'textSource' => null,
            'state' => 'future',
            'actions' => ['cancel'],
        ], $body['inscriptions'][0]);
    }

    public function testAnotherEditionIsReadWithTheEditionParameter(): void
    {
        $current = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($current);
        $past = $this->createEdition('2025-11-28 19:00:00');
        $this->em->persist(new Inscription($past->getId(), $past->getSlots()[3]->getId(), $this->mariesInscription->getPriantId()));
        $this->em->flush();
        $this->actAs('priant vérifié');

        $this->client->request('GET', \sprintf('/api/me/inscriptions?edition=%s', $past->getId()));

        $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame((string) $past->getId(), $body['editionId']);
        self::assertSame('past', $body['inscriptions'][0]['state']);
        self::assertSame([], $body['inscriptions'][0]['actions']);
    }

    public function testAnUnknownEditionIsNotFound(): void
    {
        $this->givenTheProfilesOf($this->createEdition('2099-01-01 19:00:00'));
        $this->actAs('priant vérifié');

        $this->client->request('GET', '/api/me/inscriptions?edition=01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/me/inscriptions?edition=nope');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testWithoutPublicEditionThereIsNoSpaceByDefault(): void
    {
        $edition = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($edition);
        $edition->unschedule(new \DateTimeImmutable());
        $this->em->flush();
        $this->actAs('priant vérifié');

        $this->client->request('GET', '/api/me/inscriptions');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    private function createEdition(string $startsAtUtc): Edition
    {
        $startsAt = new \DateTimeImmutable($startsAtUtc, new \DateTimeZone('UTC'));
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            $startsAt,
            // Open since 2020: the edition to come is the public one (RG-ED-1)
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }
}
