<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Domain\Time\ParisCalendar;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Acceptance test for GET /api/editions/current (F08-T02, US-08.1):
 * the current public edition, 404 when none is open, public access.
 */
final class PublicEditionApiTest extends ApiTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private EditionRepositoryInterface $editionRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->editionRepository = $container->get(EditionRepositoryInterface::class);

        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
        $this->em->clear();
    }

    public function testCurrentReturns404WhenNoEditionExists(): void
    {
        $this->client->request('GET', '/api/editions/current');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testCurrentReturns404WhenTheOnlyEditionIsADraft(): void
    {
        $this->createEdition(lifecycle: 'draft', opensAt: '2020-01-01 00:00:00', startsAt: '2099-01-01 00:00:00');

        $this->client->request('GET', '/api/editions/current');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testCurrentReturnsTheOpenEditionWithAllFields(): void
    {
        $edition = $this->createEdition(
            lifecycle: 'scheduled',
            opensAt: '2020-01-01 00:00:00',
            startsAt: '2099-01-01 00:00:00',
            title: '24 heures de prière — novembre 2099',
            presentation: 'Une chaîne de prière continue.',
        );

        $this->client->request('GET', '/api/editions/current');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($edition->getId()->value->toRfc4122(), $data['id'] ?? null);
        self::assertSame('24 heures de prière — novembre 2099', $data['title'] ?? null);
        self::assertSame('Une chaîne de prière continue.', $data['presentation'] ?? null);
        self::assertSame('open', $data['status'] ?? null);
        self::assertNotEmpty($data['startsAt'] ?? null);
        self::assertNotEmpty($data['opensAt'] ?? null);
        self::assertNotEmpty($data['startsAtLabel'] ?? null);
        self::assertNotEmpty($data['opensAtLabel'] ?? null);
    }

    public function testCurrentIsPubliclyAccessibleWithoutAuthentication(): void
    {
        $this->createEdition(lifecycle: 'scheduled', opensAt: '2020-01-01 00:00:00', startsAt: '2099-01-01 00:00:00');

        // No login: the client is anonymous
        $this->client->request('GET', '/api/editions/current');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCurrentResponseHasNoStoreCacheControl(): void
    {
        $this->createEdition(lifecycle: 'scheduled', opensAt: '2020-01-01 00:00:00', startsAt: '2099-01-01 00:00:00');

        $this->client->request('GET', '/api/editions/current');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));
    }

    private function createEdition(
        string $lifecycle,
        string $opensAt,
        string $startsAt,
        string $title = '24 heures de prière',
        string $presentation = '',
    ): Edition {
        $edition = Edition::create(
            new EditionTitle($title),
            new Presentation($presentation),
            new \DateTimeImmutable($startsAt, new \DateTimeZone('UTC')),
            new \DateTimeImmutable($opensAt, new \DateTimeZone('UTC')),
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
