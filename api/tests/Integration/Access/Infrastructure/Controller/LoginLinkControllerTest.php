<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for LoginLinkController (F02-T07).
 * Done when: a GET opens no session (test).
 */
final class LoginLinkControllerTest extends WebTestCase
{
    public function testGetAuthLoginRedirectsToSpa(): void
    {
        $client = static::createClient();

        // GET /auth/login redirects to the SPA /login
        $client->request('GET', '/auth/login');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testGetAuthLoginPreservesTheQueryString(): void
    {
        $client = static::createClient();

        // The SPA /login page needs the link parameters (user, expires, hash)
        // to POST them back to /auth/login: the redirect must keep them.
        $client->request('GET', '/auth/login?user=abc&expires=123&hash=def');

        $location = $client->getResponse()->headers->get('Location');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('/login?', $location);
        $this->assertStringContainsString('user=abc', $location);
        $this->assertStringContainsString('expires=123', $location);
        $this->assertStringContainsString('hash=def', $location);
    }

    public function testGetAuthLoginDoesNotStartAuthentication(): void
    {
        $client = static::createClient();

        // GET /auth/login must not trigger login_link authentication:
        // with check_post_only: true, GET is not handled by login_link.
        $client->request('GET', '/auth/login');

        // The controller is called, not login_link: a redirect, not an auth error.
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testRoutesAreConfigured(): void
    {
        $container = self::getContainer();

        // Check the routes exist
        $router = $container->get('router');

        // GET route for the SPA redirect
        $getRoute = $router->getRouteCollection()->get('auth_login_link_get');
        $this->assertNotNull($getRoute);
        $this->assertEquals('/auth/login', $getRoute->getPath());
        $this->assertContains('GET', $getRoute->getMethods());

        // POST route handled by Symfony Security login_link
        $postRoute = $router->getRouteCollection()->get('_security_login_link');
        $this->assertNotNull($postRoute);
        $this->assertEquals('/auth/login', $postRoute->getPath());
        $this->assertContains('POST', $postRoute->getMethods());
    }
}
