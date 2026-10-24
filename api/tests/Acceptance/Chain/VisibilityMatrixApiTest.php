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
 * The rights matrix replayed on the public resources (F10-T09, US-10.4):
 * each profile gets exactly the fields its JSON Schema allows. The schemas
 * forbid additional properties, so a new field fails here until its
 * visibility is decided.
 */
final class VisibilityMatrixApiTest extends ApiTestCase
{
    use ViewerProfiles;

    /**
     * Who sees the names and pointages (RG-PL-4, RG-LV-2), and whose is
     * Marie's slot.
     */
    private const array GRID = [
        'visiteur' => ['schema' => 'edition-slots.masked', 'mine' => false],
        'pass' => ['schema' => 'edition-slots.visible', 'mine' => true],
        'priant vérifié' => ['schema' => 'edition-slots.visible', 'mine' => true],
        'autre priant' => ['schema' => 'edition-slots.masked', 'mine' => false],
        'organisateur' => ['schema' => 'edition-slots.visible', 'mine' => false],
    ];

    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private Edition $edition;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        // As in production: the session and pass cookies are Secure
        $this->client->setServerParameter('HTTPS', 'on');

        $this->em = self::getContainer()->get('doctrine')->getManager();
        $connection = $this->em->getConnection();
        foreach (['device_pass', 'inscription', 'priant', 'slot', 'edition'] as $table) {
            $connection->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');

        $this->edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation('Une chaîne de prière.'),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($this->edition);
        $this->givenTheProfilesOf($this->edition);
    }

    #[DataProvider('viewerProfiles')]
    public function testTheGridShowsTheNamesOnlyToTheInscritsAndTheOrganizers(string $profile): void
    {
        $this->actAs($profile);

        $this->client->request('GET', \sprintf('/api/editions/%s/slots', $this->edition->getId()));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesJsonSchema((string) $response->getContent(), self::GRID[$profile]['schema']);

        $slots = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR)['slots'];
        self::assertSame(self::GRID[$profile]['mine'], $slots[0]['mine']);
        self::assertNotContains(true, \array_slice(array_column($slots, 'mine'), 1), 'Only Marie\'s slot is hers.');
        if (null !== $slots[0]['participants']) {
            self::assertSame([['name' => 'Marie Dupont', 'prayed' => false]], $slots[0]['participants']);
        }
    }

    #[DataProvider('viewerProfiles')]
    public function testTheCurrentEditionIsTheSameForEveryone(string $profile): void
    {
        $this->actAs($profile);

        $this->client->request('GET', '/api/editions/current');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesJsonSchema((string) $response->getContent(), 'edition-current');
    }
}
