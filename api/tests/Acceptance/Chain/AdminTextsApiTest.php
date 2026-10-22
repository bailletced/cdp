<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use App\Access\Domain\Entity\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Symfony\Security\UserProvider;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\ApiTestCase;
use App\Tests\Factory\Access\OrganizerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Acceptance test for GET /api/admin/texts (F06-T08): Hydra collection with
 * pagination and accent-insensitive search, item details, 404 on unknown id
 * and organizer-only security.
 */
final class AdminTextsApiTest extends ApiTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private TextRepositoryInterface $textRepository;

    private EditionRepositoryInterface $editionRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->textRepository = $container->get(TextRepositoryInterface::class);
        $this->editionRepository = $container->get(EditionRepositoryInterface::class);

        // Clean slate for each test (same isolation strategy as the Behat scenarios)
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $connection->executeStatement('DELETE FROM text');
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
        $this->em->clear();
    }

    public function testVisitorGets401OnTheTextsCollection(): void
    {
        $this->client->request('GET', '/api/admin/texts');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizerGetsTheTextsCollectionWithHydraStructure(): void
    {
        $attributed = $this->createText('Oraison de Thérèse', 'Thérèse de Lisieux');
        $this->createText('Psaume vingt-trois', 'La Bible');
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($attributed->getId()->value);
        $this->em->flush();

        $this->logInAsOrganizer();

        $this->client->request('GET', '/api/admin/texts');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(2, $data['totalItems'] ?? null);
        self::assertCount(2, $data['member']);
        // Alphabetical order, accent-insensitive: "Oraison de Thérèse" before "Psaume vingt-trois"
        self::assertSame('Oraison de Thérèse', $data['member'][0]['title']);
        self::assertSame('Thérèse de Lisieux', $data['member'][0]['source']);
        self::assertSame(1, $data['member'][0]['attributionCount']);
        self::assertSame('Psaume vingt-trois', $data['member'][1]['title']);
        self::assertSame(0, $data['member'][1]['attributionCount']);
        // The list does not embed the (heavy) body
        self::assertNull($data['member'][0]['body'] ?? null);
    }

    public function testPriantGets403OnTheTextsCollection(): void
    {
        $this->logInAsPriant();

        $this->client->request('GET', '/api/admin/texts');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizerCanSearchTextsWithAnAccentInsensitiveQuery(): void
    {
        $this->createText('Oraison de Thérèse', 'Thérèse de Lisieux');
        $this->createText('Psaume vingt-trois', 'La Bible');

        $this->logInAsOrganizer();

        $this->client->request('GET', '/api/admin/texts', ['q' => 'therese']);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(1, $data['totalItems'] ?? null);
        self::assertCount(1, $data['member']);
        self::assertSame('Oraison de Thérèse', $data['member'][0]['title']);
    }

    public function testOrganizerGetsTheHydraPaginationViewWhenThereAreSeveralPages(): void
    {
        $this->createText('Ave Maria', 'Liturgie');
        $this->createText('Kyrie eleison', 'Liturgie');
        $this->createText('Pater noster', 'Liturgie');

        $this->logInAsOrganizer();

        $this->client->request('GET', '/api/admin/texts', ['itemsPerPage' => '1', 'page' => '1']);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(3, $data['totalItems'] ?? null);
        self::assertCount(1, $data['member']);
        self::assertSame('Ave Maria', $data['member'][0]['title']);
        self::assertSame('PartialCollectionView', $data['view']['@type'] ?? null);
        self::assertArrayHasKey('first', $data['view']);
        self::assertArrayHasKey('last', $data['view']);
        self::assertArrayHasKey('next', $data['view']);
        self::assertArrayNotHasKey('previous', $data['view']);

        $this->client->request('GET', '/api/admin/texts', ['itemsPerPage' => '1', 'page' => '2']);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(3, $data['totalItems'] ?? null);
        self::assertCount(1, $data['member']);
        self::assertSame('Kyrie eleison', $data['member'][0]['title']);
        self::assertArrayHasKey('previous', $data['view']);
        self::assertArrayHasKey('next', $data['view']);
    }

    public function testOrganizerGetsTheTextDetailsWithTheBodyAndTheHints(): void
    {
        $text = $this->createText('Oraison de Thérèse', 'Thérèse de Lisieux', '<p>Corps <strong>riche</strong>.</p>', 'Méditer le matin.');
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $this->em->flush();

        $this->logInAsOrganizer();

        $this->client->request('GET', '/api/admin/texts/'.$text->getId()->value->toRfc4122());

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($text->getId()->value->toRfc4122(), $data['id'] ?? null);
        self::assertSame('Oraison de Thérèse', $data['title'] ?? null);
        self::assertSame('Thérèse de Lisieux', $data['source'] ?? null);
        self::assertSame('<p>Corps <strong>riche</strong>.</p>', $data['body'] ?? null);
        self::assertSame('Méditer le matin.', $data['hints'] ?? null);
        self::assertSame(1, $data['attributionCount'] ?? null);
    }

    public function testOrganizerGets404OnAnUnknownText(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('GET', '/api/admin/texts/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizerGets404OnAMalformedTextId(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('GET', '/api/admin/texts/id-1');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testTheTextsCollectionConformsToItsJsonSchema(): void
    {
        $this->createText('Oraison de Thérèse', 'Thérèse de Lisieux');

        $container = self::getContainer();
        $metadataFactory = $container->get(ResourceMetadataCollectionFactoryInterface::class);
        self::assertInstanceOf(ResourceMetadataCollectionFactoryInterface::class, $metadataFactory);
        $schemaFactory = $container->get(SchemaFactoryInterface::class);
        self::assertInstanceOf(SchemaFactoryInterface::class, $schemaFactory);

        $collectionOperation = null;
        foreach ($metadataFactory->create(AdminTextResource::class) as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $operation) {
                if ($operation instanceof CollectionOperationInterface) {
                    $collectionOperation = $operation;
                    break 2;
                }
            }
        }
        self::assertNotNull($collectionOperation);

        $schema = $schemaFactory->buildSchema(AdminTextResource::class, 'jsonld', Schema::TYPE_OUTPUT, $collectionOperation);
        $schemaArray = json_decode(json_encode($schema->getArrayCopy(), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($schemaArray);

        // The collection schema is an object whose member property is an array of items
        self::assertSame('object', $schemaArray['type'] ?? null);
        $memberSchema = $schemaArray['allOf'][1]['properties']['member'] ?? null;
        self::assertIsArray($memberSchema);
        self::assertSame('array', $memberSchema['type'] ?? null);

        // The Hydra base schemas declare totalItems and the pagination view
        $definitions = $schemaArray['definitions'] ?? null;
        self::assertIsArray($definitions);
        $collectionBase = $definitions['HydraCollectionBaseSchemaNoPagination'] ?? null;
        self::assertIsArray($collectionBase);
        self::assertSame('integer', $collectionBase['properties']['totalItems']['type'] ?? null);
        $paginatedBase = $definitions['HydraCollectionBaseSchema'] ?? null;
        self::assertIsArray($paginatedBase);
        $viewProperties = $paginatedBase['allOf'][1]['properties']['view']['properties'] ?? null;
        self::assertIsArray($viewProperties);
        foreach (['first', 'last', 'previous', 'next'] as $page) {
            self::assertArrayHasKey($page, $viewProperties);
        }

        // The item definition declares the AdminText properties
        $itemRef = $memberSchema['items']['$ref'] ?? null;
        self::assertIsString($itemRef);
        $position = strrpos($itemRef, '/');
        self::assertNotFalse($position);
        $itemDefinition = $definitions[substr($itemRef, $position + 1)] ?? null;
        self::assertIsArray($itemDefinition);
        $itemSchema = $itemDefinition['allOf'][1] ?? $itemDefinition;
        self::assertIsArray($itemSchema);
        $itemProperties = $itemSchema['properties'] ?? null;
        self::assertIsArray($itemProperties);
        foreach (['id', 'title', 'source', 'body', 'hints', 'attributionCount'] as $property) {
            self::assertArrayHasKey($property, $itemProperties);
        }

        // The actual response conforms to the schema
        $this->logInAsOrganizer();
        $this->client->request('GET', '/api/admin/texts');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsInt($data['totalItems'] ?? null);
        self::assertGreaterThanOrEqual(0, $data['totalItems'] ?? 0);
        self::assertIsArray($data['member'] ?? null);
        foreach ($data['member'] as $member) {
            self::assertIsArray($member);
            self::assertIsString($member['id'] ?? null);
            self::assertIsString($member['title'] ?? null);
            self::assertIsString($member['source'] ?? null);
            self::assertIsInt($member['attributionCount'] ?? null);
        }
    }

    public function testOrganizerCanCreateAText(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Oraison de Thérèse',
            'source' => 'Thérèse de Lisieux',
            'body' => '<p>Je voudrais aimer.</p>',
            'hints' => 'Méditer le matin.',
        ]));

        $response = $this->client->getResponse();
        self::assertSame(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotNull($data['id'] ?? null);
        self::assertSame('Oraison de Thérèse', $data['title'] ?? null);
        self::assertSame('Thérèse de Lisieux', $data['source'] ?? null);

        $text = $this->textRepository->findById(new \App\Chain\Domain\ValueObject\TextId($data['id']));
        self::assertNotNull($text);
        self::assertSame('Oraison de Thérèse', $text->getTitle()->value);
    }

    public function testCreateTextReturns422OnTooShortTitle(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'ab',
            'source' => 'Auteur',
            'body' => '<p>Corps.</p>',
        ]));

        $response = $this->client->getResponse();
        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('title', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testCreateTextReturns422OnEmptySource(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Titre valide',
            'source' => '   ',
            'body' => '<p>Corps.</p>',
        ]));

        $response = $this->client->getResponse();
        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('source', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testCreateTextReturns422OnEmptyBodyAfterSanitization(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Titre valide',
            'source' => 'Auteur',
            'body' => '<p></p>',
        ]));

        $response = $this->client->getResponse();
        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('body', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testVisitorGets401OnTextCreation(): void
    {
        $this->client->request('POST', '/api/admin/texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'title' => 'Titre valide',
            'source' => 'Auteur',
            'body' => '<p>Corps.</p>',
        ]));

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizerCanUpdateAText(): void
    {
        $text = $this->createText('Titre original', 'Auteur original');
        $this->logInAsOrganizer();

        $this->client->request('PATCH', '/api/admin/texts/'.$text->getId()->value->toRfc4122(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'title' => 'Titre modifié',
            'source' => 'Nouvel auteur',
            'body' => '<p>Nouveau corps.</p>',
            'hints' => 'Nouvelles pistes.',
        ]));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Titre modifié', $data['title'] ?? null);
        self::assertSame('Nouvel auteur', $data['source'] ?? null);

        $this->em->clear();
        $updated = $this->textRepository->findById($text->getId());
        self::assertNotNull($updated);
        self::assertSame('Titre modifié', $updated->getTitle()->value);
        self::assertSame('Nouvel auteur', $updated->getSource()->value);
    }

    public function testUpdateTextReturns404OnUnknownText(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('PATCH', '/api/admin/texts/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'title' => 'Titre modifié',
            'source' => 'Auteur',
            'body' => '<p>Corps.</p>',
        ]));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateTextReturns422OnInvalidPayload(): void
    {
        $text = $this->createText('Titre original', 'Auteur original');
        $this->logInAsOrganizer();

        $this->client->request('PATCH', '/api/admin/texts/'.$text->getId()->value->toRfc4122(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'title' => 'ab',
        ]));

        $response = $this->client->getResponse();
        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('title', $data['violations'][0]['propertyPath'] ?? null);
    }

    public function testOrganizerCanDeleteAText(): void
    {
        $text = $this->createText('À supprimer', 'Auteur');
        $this->logInAsOrganizer();

        $this->client->request('DELETE', '/api/admin/texts/'.$text->getId()->value->toRfc4122());

        self::assertSame(204, $this->client->getResponse()->getStatusCode());

        $this->em->clear();
        self::assertNull($this->textRepository->findById($text->getId()));
    }

    public function testDeleteTextReturns404OnUnknownText(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('DELETE', '/api/admin/texts/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteTextReturns409WhenAttributedToScheduledEdition(): void
    {
        $text = $this->createText('Attribué', 'Auteur');
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $edition->schedule(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->em->flush();

        $this->logInAsOrganizer();

        $this->client->request('DELETE', '/api/admin/texts/'.$text->getId()->value->toRfc4122());

        self::assertSame(409, $this->client->getResponse()->getStatusCode());
    }

    public function testVisitorGets401OnTextDeletion(): void
    {
        $this->client->request('DELETE', '/api/admin/texts/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    private function logInAsOrganizer(string $email = 'anne@bailletced.org'): void
    {
        $account = $this->createAccount($email);
        $this->em->persist(OrganizerFactory::create($account->getId()->value, $account->getId()->value));
        $this->em->flush();

        $this->logIn($account);
    }

    private function logInAsPriant(string $email = 'thomas@bailletced.org'): void
    {
        $this->logIn($this->createAccount($email));
    }

    private function createAccount(string $email): Account
    {
        $account = new Account(new AccountId(), new Email($email));
        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

    /**
     * Authenticate through the login-link flow (same steps as the Behat
     * contexts): generate a signed link, POST it, keep the session cookie.
     */
    private function logIn(Account $account): void
    {
        $container = self::getContainer();

        $userProvider = $container->get(UserProvider::class);
        $securityUser = $userProvider->loadUserByIdentifier($account->getId()->value->toRfc4122());

        $handler = $container->get('security.authenticator.login_link_handler.main');
        $url = $handler->createLoginLink($securityUser)->getUrl();

        $parts = parse_url($url);
        \assert(\is_string($parts['path'] ?? null));
        parse_str($parts['query'] ?? '', $params);
        $params['_target_path'] = '/my-space';

        $this->client->request('POST', $parts['path'], $params);
    }

    private function createText(string $title, string $source, string $body = '<p>Corps du texte.</p>', ?string $hints = null): Text
    {
        $text = Text::create(
            new TextTitle($title),
            new TextSource($source),
            new RichText($body),
            $hints,
        );
        $this->textRepository->add($text);
        $this->em->flush();

        return $text;
    }

    private function createEdition(): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->editionRepository->add($edition);
        $this->em->flush();

        return $edition;
    }
}
