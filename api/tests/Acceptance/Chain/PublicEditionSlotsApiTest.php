<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Domain\Time\ParisCalendar;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Acceptance test for GET /api/editions/{id}/slots (F08-T04, US-08.2):
 * the 48 public slots of a scheduled edition, 404 when the edition is
 * unknown or not public, no priant data (RG-PL-4).
 */
final class PublicEditionSlotsApiTest extends ApiTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private EditionRepositoryInterface $editionRepository;

    private TextRepositoryInterface $textRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->editionRepository = $container->get(EditionRepositoryInterface::class);
        $this->textRepository = $container->get(TextRepositoryInterface::class);

        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $connection->executeStatement('DELETE FROM text');
        $this->em->clear();
    }

    public function testSlotsReturns404ForAnUnknownEdition(): void
    {
        $this->client->request('GET', '/api/editions/'.\Symfony\Component\Uid\Uuid::v7()->toRfc4122().'/slots');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testSlotsReturns404ForADraftEdition(): void
    {
        $edition = $this->createEdition(lifecycle: 'draft');

        $this->client->request('GET', '/api/editions/'.$edition->getId()->value->toRfc4122().'/slots');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testSlotsReturns404ForAnInvalidUuid(): void
    {
        $this->client->request('GET', '/api/editions/not-a-uuid/slots');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testSlotsReturns48SlotsInRankOrderForAPublicEdition(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        $this->client->request('GET', '/api/editions/'.$edition->getId()->value->toRfc4122().'/slots');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($edition->getId()->value->toRfc4122(), $data['id'] ?? null);

        $slots = $data['slots'] ?? null;
        self::assertIsArray($slots);
        self::assertCount(48, $slots);

        foreach ($slots as $index => $slot) {
            self::assertSame($index + 1, $slot['rank'] ?? null);
            self::assertNotEmpty($slot['id'] ?? null);
            self::assertNotEmpty($slot['startsAt'] ?? null);
            self::assertNotEmpty($slot['endsAt'] ?? null);
            self::assertNotEmpty($slot['label'] ?? null);
            self::assertContains($slot['partOfDay'] ?? null, ['evening', 'night', 'morning', 'afternoon']);
            self::assertContains($slot['state'] ?? null, ['past', 'current', 'future']);
            self::assertArrayHasKey('textTitle', $slot);
            self::assertArrayHasKey('textSource', $slot);
        }
    }

    public function testSlotsExposeTextTitleAndSourceWhenAttributed(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');
        $text = Text::create(
            new TextTitle('Oraison de Thérèse'),
            new TextSource('Thérèse de Lisieux'),
            new RichText('<p>Corps.</p>'),
        );
        $this->textRepository->add($text);
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $this->em->flush();

        $this->client->request('GET', '/api/editions/'.$edition->getId()->value->toRfc4122().'/slots');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $slots = $data['slots'];
        self::assertSame('Oraison de Thérèse', $slots[0]['textTitle'] ?? null);
        self::assertSame('Thérèse de Lisieux', $slots[0]['textSource'] ?? null);
        self::assertArrayHasKey('textTitle', $slots[1]);
        self::assertNull($slots[1]['textTitle']);
        self::assertArrayHasKey('textSource', $slots[1]);
        self::assertNull($slots[1]['textSource']);
    }

    public function testSlotsExposeNoPriantData(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        $this->client->request('GET', '/api/editions/'.$edition->getId()->value->toRfc4122().'/slots');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        foreach ($data['slots'] as $slot) {
            self::assertArrayNotHasKey('registrationCount', $slot);
            self::assertArrayNotHasKey('isMine', $slot);
            self::assertArrayNotHasKey('priantName', $slot);
        }
    }

    public function testSlotsIsPubliclyAccessibleWithoutAuthentication(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        // No login: the client is anonymous
        $this->client->request('GET', '/api/editions/'.$edition->getId()->value->toRfc4122().'/slots');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testSlotsResponseHasNoStoreCacheControl(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        $this->client->request('GET', '/api/editions/'.$edition->getId()->value->toRfc4122().'/slots');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));
    }

    private function createEdition(string $lifecycle): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        if ('scheduled' === $lifecycle) {
            $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        }

        $this->editionRepository->add($edition);
        $this->em->flush();

        return $edition;
    }
}
