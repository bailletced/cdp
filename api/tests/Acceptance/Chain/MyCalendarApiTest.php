<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Domain\Time\ParisCalendar;
use App\Tests\Acceptance\ViewerProfiles;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * GET /api/me/calendar.ics (F11-T06, US-11.2): the single agenda file of
 * the verified priant's slots in the current edition. A device pass only
 * reads, it never gets the priant's file (RG-IN-6).
 */
final class MyCalendarApiTest extends ApiTestCase
{
    use ViewerProfiles;

    /**
     * HTTP status and number of events, per profile.
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
    public function testOnlyAVerifiedPriantDownloadsItsAgendaFile(string $profile): void
    {
        $this->givenTheProfilesOf($this->createOpenEdition());
        $this->actAs($profile);

        $this->client->request('GET', '/api/me/calendar.ics');

        $response = $this->client->getResponse();
        [$status, $events] = self::EXPECTED[$profile];
        self::assertSame($status, $response->getStatusCode());
        if (null !== $events) {
            self::assertStringStartsWith('text/calendar', (string) $response->headers->get('Content-Type'));
            self::assertStringContainsString('attachment; filename=mes-creneaux.ics', (string) $response->headers->get('Content-Disposition'));
            self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
            self::assertSame($events, substr_count((string) $response->getContent(), 'BEGIN:VEVENT'));
        }
    }

    public function testWithoutPublicEditionThereIsNoFile(): void
    {
        $edition = $this->createOpenEdition();
        $this->givenTheProfilesOf($edition);
        $edition->unschedule(new \DateTimeImmutable());
        $this->em->flush();
        $this->actAs('priant vérifié');

        $this->client->request('GET', '/api/me/calendar.ics');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    private function createOpenEdition(): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($edition);

        return $edition;
    }
}
