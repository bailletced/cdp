<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * GET /api/editions/{id}/slot-suggestion?near=&exclude= (F13-T03, US-13.2,
 * US-13.3): an empty slot still to come — right after `near`, else right
 * before, else at random — or 404 when the chain is complete.
 */
final class SlotSuggestionApiTest extends ApiTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private Edition $edition;

    private Priant $priant;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->em = self::getContainer()->get('doctrine')->getManager();
        foreach (['inscription', 'priant', 'slot', 'edition', 'text'] as $table) {
            $this->em->getConnection()->executeStatement(\sprintf('DELETE FROM %s', $table));
        }

        // Starts on 2099-01-01 at 20h00 in Paris
        $this->edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($this->edition);
        $this->priant = new Priant(new Email('thomas@bailletced.org'), new DisplayName('Famille Martin'));
        $this->em->persist($this->priant);
        $this->em->flush();
    }

    public function testTheSlotRightAfterTheOneJustTakenComesFirst(): void
    {
        $text = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Texte.</p>'));
        $this->em->persist($text);
        $this->edition->assignText($this->edition->getSlots()[5]->getId(), $text->getId());
        $this->register(4);

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion?near=%s', $this->edition->getId(), $this->slotId(4)));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $body = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame((string) $this->edition->getId(), $body['id']);
        self::assertSame($this->slotId(5), $body['slotId']);
        self::assertSame('22h30–23h00', $body['label']);
        self::assertSame('2099-01-01T21:30:00+00:00', $body['startsAt']);
        self::assertSame('2099-01-01T22:00:00+00:00', $body['endsAt']);
        self::assertSame('Que rien ne te trouble', $body['textTitle']);
        self::assertSame('Thérèse d’Avila', $body['textSource']);
    }

    public function testTheSlotRightBeforeComesNextWhenTheFollowingOneIsTaken(): void
    {
        $this->register(4, 5);

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion?near=%s', $this->edition->getId(), $this->slotId(4)));

        self::assertSame($this->slotId(3), $this->suggestedSlotId());
    }

    public function testAnotherDrawSkipsTheExcludedSlots(): void
    {
        $this->register(...array_values(array_diff(range(0, 47), [10, 20])));

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion?exclude=%s', $this->edition->getId(), $this->slotId(10)));

        self::assertSame($this->slotId(20), $this->suggestedSlotId());
    }

    public function testWithoutNearTheDrawOnlyOffersEmptySlots(): void
    {
        $this->register(...array_values(array_diff(range(0, 47), [12, 30, 41])));

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion', $this->edition->getId()));

        self::assertContains($this->suggestedSlotId(), [$this->slotId(12), $this->slotId(30), $this->slotId(41)]);
    }

    public function testACompleteChainHasNoSuggestion(): void
    {
        $this->register(...range(0, 47));

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion', $this->edition->getId()));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testADraftEditionHasNoSuggestion(): void
    {
        $this->edition->unschedule(new \DateTimeImmutable('2020-01-02 00:00:00', new \DateTimeZone('UTC')));
        $this->em->flush();

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion', $this->edition->getId()));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAnUnknownOrMalformedEditionHasNoSuggestion(): void
    {
        $this->client->request('GET', '/api/editions/01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b/slot-suggestion');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/editions/not-a-uuid/slot-suggestion');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testMalformedNearOrExcludeIdsAreIgnored(): void
    {
        $this->register(...array_values(array_diff(range(0, 47), [7])));

        $this->client->request('GET', \sprintf('/api/editions/%s/slot-suggestion?near=nope&exclude=nope,%s', $this->edition->getId(), $this->slotId(7)));

        // The only empty slot comes back, even excluded: « tant que possible »
        self::assertSame($this->slotId(7), $this->suggestedSlotId());
    }

    private function suggestedSlotId(): string
    {
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        return json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR)['slotId'];
    }

    private function slotId(int $index): string
    {
        return (string) $this->edition->getSlots()[$index]->getId();
    }

    private function register(int ...$slotIndexes): void
    {
        foreach ($slotIndexes as $index) {
            $this->em->persist(new Inscription($this->edition->getId(), $this->edition->getSlots()[$index]->getId(), $this->priant->getId()));
        }
        $this->em->flush();
    }
}
