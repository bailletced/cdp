<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Controller;

use App\Shared\Application\Port\DatabaseHealth;
use App\Shared\Infrastructure\Controller\HealthController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HealthController (F03-T06).
 * Done when: 200 when database is up and schema up-to-date, 503 otherwise.
 */
final class HealthControllerTest extends TestCase
{
    public function testReturns200WhenDatabaseIsUpAndSchemaUpToDate(): void
    {
        $databaseHealth = new FakeDatabaseHealth(true, 'up-to-date');
        $controller = new HealthController($databaseHealth, 'abc123');

        $response = $controller->health();

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('ok', $data['status']);
        $this->assertSame('abc123', $data['version']);
        $this->assertSame('up', $data['database']);
        $this->assertSame('up-to-date', $data['schema']);
    }

    public function testReturns503WhenDatabaseIsDown(): void
    {
        $databaseHealth = new FakeDatabaseHealth(false, 'unknown');
        $controller = new HealthController($databaseHealth, 'abc123');

        $response = $controller->health();

        $this->assertSame(503, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('error', $data['status']);
        $this->assertSame('down', $data['database']);
    }

    public function testReturns503WhenSchemaIsNotUpToDate(): void
    {
        $databaseHealth = new FakeDatabaseHealth(true, 'no-migrations');
        $controller = new HealthController($databaseHealth, 'abc123');

        $response = $controller->health();

        $this->assertSame(503, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('error', $data['status']);
        $this->assertSame('up', $data['database']);
        $this->assertSame('no-migrations', $data['schema']);
    }
}

final class FakeDatabaseHealth implements DatabaseHealth
{
    public function __construct(
        private readonly bool $up,
        private readonly string $schemaStatus,
    ) {
    }

    public function isUp(): bool
    {
        return $this->up;
    }

    public function schemaStatus(): string
    {
        return $this->schemaStatus;
    }
}
