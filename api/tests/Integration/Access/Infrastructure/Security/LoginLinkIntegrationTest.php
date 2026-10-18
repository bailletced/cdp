<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Security;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Integration tests for login_link (F02-T08).
 * Done when: POST /auth/login opens the session and returns the target.
 * The 4 cases: valid, expired, tampered, revoked link → typed 401.
 */
final class LoginLinkIntegrationTest extends WebTestCase
{
    /**
     * Tests that GET /auth/login redirects to the SPA /login (F02-T07).
     */
    public function testGetAuthLoginRedirectsToSpa(): void
    {
        $client = static::createClient();

        // GET /auth/login redirects to /login
        $client->request('GET', '/auth/login');

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/login', $response->headers->get('Location'));
    }

    /**
     * Tests that a POST with a valid token opens the session and returns the target.
     */
    public function testPostWithValidTokenOpensSessionAndReturnsTarget(): void
    {
        $client = static::createClient();

        // Create an account for the test with a unique email
        $accountUuid = Uuid::v7();
        $account = new Account(
            new AccountId($accountUuid),
            new Email('test-login-link-valid-'.$accountUuid->toRfc4122().'@example.com'),
            1
        );

        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($account);
        $em->flush();

        // Generate a login link with the target
        $loginLinkParams = $this->generateLoginLinkParamsForUuid($accountUuid);

        // POST /auth/login with the login link parameters
        // login_link expects user, expires, hash and _target_path
        $client->request('POST', '/auth/login', array_merge(
            $loginLinkParams,
            ['_target_path' => '/my-space']
        ));

        $response = $client->getResponse();

        // Must return 200 with JSON containing the target
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('target', $data);
        $this->assertEquals('/my-space', $data['target']);

        // Cleanup
        $em->remove($account);
        $em->flush();
    }

    /**
     * Tests that an invalid token returns 401.
     */
    public function testPostWithInvalidTokenReturns401(): void
    {
        $client = static::createClient();

        // POST with obviously invalid parameters
        $client->request('POST', '/auth/login', [
            'user' => 'invalid_user_id',
            'expires' => time() + 3600,
            'hash' => 'invalid_hash',
        ]);

        $response = $client->getResponse();

        // Must return 401
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('invalid_login_link', $data['error']);
    }

    /**
     * Tests that a tampered token returns 401.
     */
    public function testPostWithAlteredTokenReturns401(): void
    {
        $client = static::createClient();

        // Create an account with a unique email
        $accountUuid = Uuid::v7();
        $account = new Account(
            new AccountId($accountUuid),
            new Email('test-login-link-altered-'.$accountUuid->toRfc4122().'@example.com'),
            1
        );

        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($account);
        $em->flush();

        // Generate a valid link then tamper with the hash
        $loginLinkParams = $this->generateLoginLinkParamsForUuid($accountUuid);
        $loginLinkParams['hash'] .= 'ALTERED';

        $client->request('POST', '/auth/login', array_merge(
            $loginLinkParams,
            ['_target_path' => '/my-space']
        ));

        $response = $client->getResponse();

        // Must return 401
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('invalid_login_link', $data['error']);

        // Cleanup
        $em->remove($account);
        $em->flush();
    }

    /**
     * Tests that a token for a revoked (deleted) account returns 401.
     */
    public function testPostWithRevokedLinkReturns401(): void
    {
        $client = static::createClient();

        // Create a temporary account with a unique email
        $tempUuid = Uuid::v7();
        $tempAccount = new Account(
            new AccountId($tempUuid),
            new Email('test-login-link-revoked-'.$tempUuid->toRfc4122().'@example.com'),
            1
        );

        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($tempAccount);
        $em->flush();

        // Generate a link for this account
        $loginLinkParams = $this->generateLoginLinkParamsForUuid($tempUuid);

        // Delete the account (revoke the link)
        $em->remove($tempAccount);
        $em->flush();

        // Test with the revoked link
        $client->request('POST', '/auth/login', array_merge(
            $loginLinkParams,
            ['_target_path' => '/my-space']
        ));

        $response = $client->getResponse();

        // Must return 401
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('invalid_login_link', $data['error']);
    }

    /**
     * Generates the login_link parameters for a given UUID.
     * Returns an array with user, expires, hash.
     */
    private function generateLoginLinkParamsForUuid(Uuid $uuid): array
    {
        $container = self::getContainer();

        // Load the SecurityUser
        $userProvider = $container->get('App\\Access\\Infrastructure\\Security\\UserProvider');
        $securityUser = $userProvider->loadUserByIdentifier($uuid->toRfc4122());

        // Generate the link via the LoginLinkHandler
        $handler = $container->get('security.authenticator.login_link_handler.main');

        // createLoginLink returns a LoginLinkDetails
        $loginLinkDetails = $handler->createLoginLink($securityUser);

        // Extract the parameters from the generated URL
        $url = $loginLinkDetails->getUrl();

        // Extract the parameters (format: /auth/login?user=...&expires=...&hash=...)
        parse_str(parse_url($url, \PHP_URL_QUERY), $queryParams);

        return [
            'user' => $queryParams['user'] ?? '',
            'expires' => $queryParams['expires'] ?? '',
            'hash' => $queryParams['hash'] ?? '',
        ];
    }
}
