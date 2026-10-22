<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Chain;

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
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\ApiTestCase;
use App\Tests\Factory\Access\OrganizerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Acceptance test for the text attribution endpoints (F07-T03):
 * PUT /api/admin/slots/{id}/text and POST /api/admin/editions/{id}/distribute-texts.
 */
final class AdminAttributionApiTest extends ApiTestCase
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
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
        $this->em->clear();
    }

    public function testOrganizerCanAssignATextToASlot(): void
    {
        $edition = $this->createEdition();
        $text = $this->createText('Oraison');
        $slot = $edition->getSlots()[0];
        $this->logInAsOrganizer();

        $this->client->request('PUT', '/api/admin/slots/'.$slot->getId()->value->toRfc4122().'/text', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode(['textId' => $text->getId()->value->toRfc4122()]));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $this->em->clear();
        $updated = $this->editionRepository->findById($edition->getId());
        self::assertNotNull($updated);
        self::assertTrue($updated->getSlots()[0]->getTextId()?->equals($text->getId()->value));
    }

    public function testOrganizerCanWithdrawATextFromASlot(): void
    {
        $edition = $this->createEdition();
        $text = $this->createText('Oraison');
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $this->em->flush();
        $slot = $edition->getSlots()[0];
        $this->logInAsOrganizer();

        $this->client->request('PUT', '/api/admin/slots/'.$slot->getId()->value->toRfc4122().'/text', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode(['textId' => null]));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $this->em->clear();
        $updated = $this->editionRepository->findById($edition->getId());
        self::assertNotNull($updated);
        self::assertNull($updated->getSlots()[0]->getTextId());
    }

    public function testAssignTextReturns404OnUnknownSlot(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('PUT', '/api/admin/slots/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d/text', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode(['textId' => null]));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAssignTextReturns404OnUnknownText(): void
    {
        $edition = $this->createEdition();
        $slot = $edition->getSlots()[0];
        $this->logInAsOrganizer();

        $this->client->request('PUT', '/api/admin/slots/'.$slot->getId()->value->toRfc4122().'/text', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode(['textId' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d']));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testVisitorGets401OnSlotTextAssignment(): void
    {
        $this->client->request('PUT', '/api/admin/slots/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d/text', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode(['textId' => null]));

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizerCanDistributeTextsAcrossSlots(): void
    {
        $edition = $this->createEdition();
        $text1 = $this->createText('Texte un');
        $text2 = $this->createText('Texte deux');
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/editions/'.$edition->getId()->value->toRfc4122().'/distribute-texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'textIds' => [$text1->getId()->value->toRfc4122(), $text2->getId()->value->toRfc4122()],
            'fromRank' => 1,
        ]));

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $this->em->clear();
        $updated = $this->editionRepository->findById($edition->getId());
        self::assertNotNull($updated);
        self::assertTrue($updated->getSlots()[0]->getTextId()?->equals($text1->getId()->value));
        self::assertTrue($updated->getSlots()[1]->getTextId()?->equals($text2->getId()->value));
        self::assertNull($updated->getSlots()[2]->getTextId());
    }

    public function testDistributeTextsReturns404OnUnknownEdition(): void
    {
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/editions/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d/distribute-texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'textIds' => [],
            'fromRank' => 1,
        ]));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDistributeTextsReturns404OnUnknownText(): void
    {
        $edition = $this->createEdition();
        $this->logInAsOrganizer();

        $this->client->request('POST', '/api/admin/editions/'.$edition->getId()->value->toRfc4122().'/distribute-texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'textIds' => ['9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'],
            'fromRank' => 1,
        ]));

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testVisitorGets401OnDistributeTexts(): void
    {
        $this->client->request('POST', '/api/admin/editions/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d/distribute-texts', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'textIds' => [],
            'fromRank' => 1,
        ]));

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    private function logInAsOrganizer(string $email = 'anne@bailletced.org'): void
    {
        $account = $this->createAccount($email);
        $this->em->persist(OrganizerFactory::create($account->getId()->value, $account->getId()->value));
        $this->em->flush();

        $this->logIn($account);
    }

    private function createAccount(string $email): Account
    {
        $account = new Account(new AccountId(), new Email($email));
        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

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

    private function createText(string $title): Text
    {
        $text = Text::create(
            new TextTitle($title),
            new TextSource('Auteur'),
            new RichText('<p>Corps du texte.</p>'),
        );
        $this->textRepository->add($text);
        $this->em->flush();

        return $text;
    }
}
