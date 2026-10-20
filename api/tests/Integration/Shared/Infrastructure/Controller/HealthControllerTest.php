<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for HealthController (F03-T06).
 * Done when: GET /health returns 200 when the database is reachable,
 * 503 otherwise, with version (SHA) and schema status.
 */
final class HealthControllerTest extends WebTestCase
{
    public function testHealthReturns200WithDatabaseInfo(): void
    {
        $client = static::createClient();

        $client->request('GET', '/health');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('database', $data);
        $this->assertArrayHasKey('schema', $data);

        $this->assertSame('ok', $data['status']);
        $this->assertSame('up', $data['database']);
        $this->assertSame('up-to-date', $data['schema']);
    }

    public function testHealthContainsVersionFromEnvironment(): void
    {
        $client = static::createClient();

        $client->request('GET', '/health');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        // In test env, APP_SHA may be 'unknown' or a real SHA — just check it's a string.
        $this->assertIsString($data['version']);
    }

    public function testHealthRouteIsConfigured(): void
    {
        $container = self::getContainer();
        $router = $container->get('router');

        $route = $router->getRouteCollection()->get('health');
        $this->assertNotNull($route);
        $this->assertEquals('/health', $route->getPath());
        $this->assertContains('GET', $route->getMethods());
    }
}
