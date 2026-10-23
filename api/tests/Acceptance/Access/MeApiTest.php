<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Access;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Symfony\Security\UserProvider;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Acceptance test for GET /api/me (F02-T16, F09-T14): the authenticated
 * account gets its email and its organizer flag, and — when linked to a
 * priant — the display name to pre-fill and the consent state (RG-IN-7).
 */
final class MeApiTest extends ApiTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private PriantRepositoryInterface $priantRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->priantRepository = $container->get(PriantRepositoryInterface::class);

        // Clean slate for each test (same isolation strategy as the Behat scenarios)
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM priant');
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
        $this->em->clear();
    }

    public function testVisitorGets401(): void
    {
        $this->client->request('GET', '/api/me');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testAccountWithoutPriantGetsNoDisplayNameAndNoConsent(): void
    {
        $this->logIn($this->createAccount('thomas@bailletced.org'));

        $this->client->request('GET', '/api/me');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('thomas@bailletced.org', $data['email'] ?? null);
        self::assertFalse($data['isOrganizer'] ?? null);
        self::assertNull($data['displayName'] ?? null);
        self::assertFalse($data['consentGiven'] ?? null);
    }

    public function testVerifiedPriantGetsTheDisplayNameAndTheConsentAlreadyGiven(): void
    {
        $account = $this->createAccount('marie@bailletced.org');
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $priant->giveConsent(new \DateTimeImmutable('2026-10-20 08:00:00', new \DateTimeZone('UTC')));
        $this->priantRepository->add($priant);
        $account->updatePriantId($priant->getId()->value->toRfc4122());
        $this->em->flush();

        $this->logIn($account);

        $this->client->request('GET', '/api/me');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('marie@bailletced.org', $data['email'] ?? null);
        // RG-IN-7: the display name is pre-filled and the consent already given.
        self::assertSame('Marie Dupont', $data['displayName'] ?? null);
        self::assertTrue($data['consentGiven'] ?? null);
    }

    public function testVerifiedPriantWithoutConsentStillGetsTheConsentCheckbox(): void
    {
        $account = $this->createAccount('jean@bailletced.org');
        $priant = new Priant(new Email('jean@bailletced.org'), new DisplayName('Jean Martin'));
        $this->priantRepository->add($priant);
        $account->updatePriantId($priant->getId()->value->toRfc4122());
        $this->em->flush();

        $this->logIn($account);

        $this->client->request('GET', '/api/me');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Jean Martin', $data['displayName'] ?? null);
        self::assertFalse($data['consentGiven'] ?? null);
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
}
