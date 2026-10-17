<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Tests\ApiTestCase;

/**
 * Test d'acceptation factice pour vérifier que la suite Acceptance fonctionne.
 * Pour F00-T19 : PHPUnit 13 strict avec suites séparées.
 */
final class AcceptanceTest extends ApiTestCase
{
    public function testAcceptanceSuiteWorks(): void
    {
        // Vérifier que le client peut être créé
        $client = static::createClient();
        $this->assertInstanceOf(\Symfony\Component\HttpKernel\Client::class, $client);
    }
}
