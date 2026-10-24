<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Domain\Time\ParisCalendar;
use App\Tests\Acceptance\ViewerProfiles;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * GET /api/slots/{id}/text (F12-T02, US-12.1, US-12.2): the full text of a
 * slot is read by the priants registered on it — past editions included —
 * and by the organizers (RG-TX-1). Anyone else gets a 404, as for a slot
 * that does not exist.
 */
final class SlotTextApiTest extends ApiTestCase
{
    use ViewerProfiles;

    private KernelBrowser $client;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('HTTPS', 'on');

        $this->em = self::getContainer()->get('doctrine')->getManager();
        $connection = $this->em->getConnection();
        foreach (['device_pass', 'inscription', 'priant', 'slot', 'edition', 'text'] as $table) {
            $connection->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
    }

    public function testAPriantReadsTheFullTextOfItsSlot(): void
    {
        $edition = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($edition);
        $slot = $edition->getSlots()[0];
        $text = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Que rien ne te <strong>trouble</strong>.</p>'), 'Relire lentement.');
        $this->em->persist($text);
        $edition->assignText($slot->getId(), $text->getId());
        $this->em->flush();
        $this->actAs('priant vérifié');

        $this->client->request('GET', \sprintf('/api/slots/%s/text', $slot->getId()));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $body = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame((string) $slot->getId(), $body['id']);
        self::assertSame((string) $edition->getId(), $body['editionId']);
        self::assertSame('24 heures de prière', $body['editionTitle']);
        self::assertSame('2099-01-01T19:00:00+00:00', $body['startsAt']);
        self::assertSame('2099-01-01T19:30:00+00:00', $body['endsAt']);
        self::assertSame('20h00–20h30', $body['label']);
        self::assertSame('jeudi 1 janvier', $body['dayLabel']);
        self::assertSame([
            'title' => 'Que rien ne te trouble',
            'source' => 'Thérèse d’Avila',
            'body' => '<p>Que rien ne te <strong>trouble</strong>.</p>',
            'hints' => 'Relire lentement.',
        ], $body['text']);
    }

    public function testASlotWithoutTextAnswersANullText(): void
    {
        $edition = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($edition);
        $this->actAs('pass');

        $this->client->request('GET', \sprintf('/api/slots/%s/text', $edition->getSlots()[0]->getId()));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('text', $body);
        self::assertNull($body['text']);
    }

    public function testAnotherSlotOfTheEditionIsNotFound(): void
    {
        $edition = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($edition);
        $this->actAs('priant vérifié');

        $this->client->request('GET', \sprintf('/api/slots/%s/text', $edition->getSlots()[1]->getId()));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAnOrganizerReadsAnyText(): void
    {
        $edition = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($edition);
        $this->actAs('organisateur');

        $this->client->request('GET', \sprintf('/api/slots/%s/text', $edition->getSlots()[30]->getId()));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAVerifiedPriantStillReadsTheTextsOfAPastEdition(): void
    {
        $current = $this->createEdition('2099-01-01 19:00:00');
        $this->givenTheProfilesOf($current);
        $past = $this->createEdition('2025-11-28 19:00:00');
        $this->em->persist(new Inscription($past->getId(), $past->getSlots()[4]->getId(), $this->mariesInscription->getPriantId()));
        $this->em->flush();
        $this->actAs('priant vérifié');

        $this->client->request('GET', \sprintf('/api/slots/%s/text', $past->getSlots()[4]->getId()));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnUnknownOrMalformedSlotIsNotFound(): void
    {
        $this->givenTheProfilesOf($this->createEdition('2099-01-01 19:00:00'));
        $this->actAs('organisateur');

        $this->client->request('GET', '/api/slots/01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b/text');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/slots/not-a-uuid/text');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    private function createEdition(string $startsAtUtc): Edition
    {
        $startsAt = new \DateTimeImmutable($startsAtUtc, new \DateTimeZone('UTC'));
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            $startsAt,
            $startsAt->modify('-15 days'),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }
}
