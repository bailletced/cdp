<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use App\Shared\Application\Port\DatabaseHealth;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Health check endpoint (F03-T06).
 * GET /health pings PostgreSQL, reports the app version (SHA) and schema status.
 * Returns 200 when healthy, 503 when the database is unreachable.
 */
final class HealthController
{
    public function __construct(
        private readonly DatabaseHealth $databaseHealth,
        private readonly string $appSha,
    ) {
    }

    public function health(): JsonResponse
    {
        $databaseUp = $this->databaseHealth->isUp();
        $schemaStatus = $this->databaseHealth->schemaStatus();

        $healthy = $databaseUp && 'up-to-date' === $schemaStatus;

        return new JsonResponse([
            'status' => $healthy ? 'ok' : 'error',
            'version' => $this->appSha,
            'database' => $databaseUp ? 'up' : 'down',
            'schema' => $schemaStatus,
        ], $healthy ? 200 : 503);
    }
}
