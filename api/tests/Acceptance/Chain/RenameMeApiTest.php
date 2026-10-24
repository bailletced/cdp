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
 * PATCH /api/me (F14-T04, US-14.2): the verified priant changes its display
 * name, at any time; it shows everywhere at once (RG-IN-2). A device pass
 * only reads (RG-IN-6).
 */
final class RenameMeApiTest extends ApiTestCase
{
    use ViewerProfiles;

    private const array EXPECTED = [
        'visiteur' => 401,
        'pass' => 401,
        'priant vérifié' => 200,
        'autre priant' => 200,
        'organisateur' => 403,
    ];

    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private Edition $edition;

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

        $this->edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($this->edition);
        $this->givenTheProfilesOf($this->edition);
    }

    #[DataProvider('viewerProfiles')]
    public function testOnlyAVerifiedPriantChangesItsName(string $profile): void
    {
        $this->actAs($profile);

        $this->patch(['displayName' => 'Famille Martin-Dubois']);

        self::assertSame(self::EXPECTED[$profile], $this->client->getResponse()->getStatusCode());
    }

    public function testTheNewNameShowsInTheGrid(): void
    {
        $this->actAs('priant vérifié');

        $this->patch(['displayName' => '  Marie Dupont-Lefèvre ']);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame('Marie Dupont-Lefèvre', json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['displayName']);

        $this->client->request('GET', \sprintf('/api/editions/%s/slots', $this->edition->getId()));
        $slots = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['slots'];
        self::assertSame([['name' => 'Marie Dupont-Lefèvre', 'prayed' => false]], $slots[0]['participants']);
    }

    public function testAnEmptyOrTooLongNameIsRefusedOnTheField(): void
    {
        $this->actAs('priant vérifié');

        foreach (['', '   ', str_repeat('a', 81)] as $invalid) {
            $this->patch(['displayName' => $invalid]);

            self::assertSame(422, $this->client->getResponse()->getStatusCode(), \sprintf('« %s »', $invalid));
            $violations = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['violations'];
            self::assertSame('displayName', $violations[0]['propertyPath']);
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    private function patch(array $body): void
    {
        $this->client->request('PATCH', '/api/me', [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ORIGIN' => 'https://localhost',
        ], json_encode($body, \JSON_THROW_ON_ERROR));
    }
}
