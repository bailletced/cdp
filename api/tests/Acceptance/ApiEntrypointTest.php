<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use ApiPlatform\Test\ApiTestCase;

final class ApiEntrypointTest extends ApiTestCase
{
    public function testTheApiEntrypointAnswersInJsonLd(): void
    {
        self::createClient()->request('GET', '/api', ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['@type' => 'Entrypoint']);
    }
}
