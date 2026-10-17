<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use PHPUnit\Framework\TestCase;

/**
 * Test d'intégration factice pour vérifier que la suite Integration fonctionne.
 * Pour F00-T19 : PHPUnit 13 strict avec suites séparées.
 */
final class IntegrationTest extends TestCase
{
    public function testIntegrationSuiteWorks(): void
    {
        $this->assertTrue(true, 'Integration test suite is working');
    }
}
