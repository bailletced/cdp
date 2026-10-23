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
 * Acceptance test for POST /api/inscriptions (F09-T10): the public
 * registration endpoint — 201 with the created inscriptions, 422 with one
 * violation per field, 404 on an unknown slot, 409 on a closed edition or a
 * past slot (F09-T08), and the honeypot rule (§6.7).
 */
final class InscriptionApiTest extends ApiTestCase
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

        // Clean slate for each test (same isolation strategy as the Behat scenarios)
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM inscription');
        $connection->executeStatement('DELETE FROM priant');
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $this->em->clear();
    }

    public function testItRegistersAPriantOnSeveralSlots(): void
    {
        $edition = $this->createOpenEdition();

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [
                $edition->getSlots()[0]->getId()->value->toRfc4122(),
                $edition->getSlots()[1]->getId()->value->toRfc4122(),
            ],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(201, $response['status']);

        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotNull($data['priantId'] ?? null);
        self::assertSame('Famille Martin', $data['displayName'] ?? null);
        self::assertCount(2, $data['inscriptions'] ?? []);
        self::assertSame($edition->getSlots()[0]->getId()->value->toRfc4122(), $data['inscriptions'][0]['slotId']);
        self::assertSame($edition->getId()->value->toRfc4122(), $data['inscriptions'][0]['editionId']);
        self::assertNotNull($data['inscriptions'][0]['id'] ?? null);

        // Persisted: one priant, two inscriptions (RG-PL-1: one inscription counts for one).
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM priant'));
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM inscription'));
    }

    public function testMissingEmailReturns422OnEmail(): void
    {
        $edition = $this->createOpenEdition();

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'consent' => true,
        ]);

        self::assertSame(422, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('email', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testInvalidEmailReturns422OnEmail(): void
    {
        $edition = $this->createOpenEdition();

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'not-an-email',
            'consent' => true,
        ]);

        self::assertSame(422, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('email', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testMissingDisplayNameReturns422OnDisplayName(): void
    {
        $edition = $this->createOpenEdition();

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(422, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('displayName', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testMissingSlotIdsReturns422OnSlotIds(): void
    {
        $response = $this->postJson('/api/inscriptions', [
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(422, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('slotIds', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testAnInvalidSlotIdReturns422OnSlotIds(): void
    {
        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => ['not-a-uuid'],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(422, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('slotIds', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testAVisitorWithoutConsentReturns422OnConsent(): void
    {
        $edition = $this->createOpenEdition();

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
        ]);

        self::assertSame(422, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('consent', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testAnUnknownSlotReturns404(): void
    {
        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [\Symfony\Component\Uid\Uuid::v4()->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(404, $response['status']);
    }

    public function testAnEditionClosedForInscriptionsReturns409(): void
    {
        // Scheduled but not open yet: inscriptions are closed.
        $edition = $this->createEdition(
            startsAtUtc: '2099-07-01 19:00:00',
            opensAtUtc: '2099-06-01 00:00:00',
        );

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(409, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Les inscriptions ne sont pas ouvertes pour cette édition.', $data['detail'] ?? null);
    }

    public function testAPastSlotReturns409(): void
    {
        // Edition running since one hour ago: the first slot is over.
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $roundedDown = $now->modify(\sprintf('-%d minutes', ((int) $now->format('i')) % 30));
        $startsAt = $roundedDown->modify('-60 minutes');
        $edition = $this->createEdition(
            startsAtUtc: $startsAt->format('Y-m-d H:i:s'),
            opensAtUtc: $startsAt->modify('-30 days')->format('Y-m-d H:i:s'),
        );

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ]);

        self::assertSame(409, $response['status']);
        $data = json_decode($response['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Ce créneau est déjà passé.', $data['detail'] ?? null);
    }

    public function testADuplicateRegistrationReturns409(): void
    {
        $edition = $this->createOpenEdition();
        $payload = [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
        ];

        $first = $this->postJson('/api/inscriptions', $payload);
        self::assertSame(201, $first['status']);

        // Double click / concurrent request: the unique index (slot, priant)
        // rejects the second registration (RG-IN-3, F09-T11).
        $second = $this->postJson('/api/inscriptions', $payload);
        self::assertSame(409, $second['status']);

        $data = json_decode($second['content'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Vous êtes déjà inscrit sur ce créneau.', $data['detail'] ?? null);

        // The second transaction rolled back entirely.
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM inscription'));
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM priant'));
    }

    public function testAFilledHoneypotReturns201AndRegistersNothing(): void
    {
        $edition = $this->createOpenEdition();

        $response = $this->postJson('/api/inscriptions', [
            'slotIds' => [$edition->getSlots()[0]->getId()->value->toRfc4122()],
            'displayName' => 'Famille Martin',
            'email' => 'famille.martin@bailletced.org',
            'consent' => true,
            'website' => 'http://spam.example',
        ]);

        // §6.7: identical-looking success, but nothing is done.
        self::assertSame(201, $response['status']);
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM priant'));
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM inscription'));
    }

    /**
     * @return array{status: int, content: string, headers: array<string, mixed>}
     */
    private function postJson(string $url, array $body): array
    {
        $this->client->request('POST', $url, [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode($body, \JSON_THROW_ON_ERROR));

        return [
            'status' => $this->client->getResponse()->getStatusCode(),
            'content' => $this->client->getResponse()->getContent(),
            'headers' => $this->client->getResponse()->headers->all(),
        ];
    }

    /**
     * An edition open for inscriptions: every slot is in the future.
     */
    private function createOpenEdition(): Edition
    {
        return $this->createEdition(
            startsAtUtc: '2099-01-01 19:00:00',
            opensAtUtc: '2020-01-01 00:00:00',
        );
    }

    private function createEdition(string $startsAtUtc, string $opensAtUtc): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable($startsAtUtc, new \DateTimeZone('UTC')),
            new \DateTimeImmutable($opensAtUtc, new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->editionRepository->add($edition);
        $this->em->flush();

        return $edition;
    }
}
