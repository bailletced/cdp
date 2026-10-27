<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use AltchaOrg\Altcha\Algorithm\Sha;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Tests\ApiTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * F15-T05 (US-15.3): the ALTCHA challenge, switched on by ALTCHA_ENABLED.
 * GET /api/altcha-challenge tells the SPA whether to show the widget (204
 * while off) and hands it a challenge; once on, an inscription or a link
 * request without a valid solution is refused (422).
 */
final class AltchaApiTest extends ApiTestCase
{
    private KernelBrowser $client;

    /** The value from .env, given back to the next tests */
    private mixed $altchaEnabled;

    protected function setUp(): void
    {
        $this->altchaEnabled = $_SERVER['ALTCHA_ENABLED'] ?? null;
    }

    protected function tearDown(): void
    {
        $_SERVER['ALTCHA_ENABLED'] = $_ENV['ALTCHA_ENABLED'] = $this->altchaEnabled;
        parent::tearDown();
    }

    public function testOffByDefaultNoChallengeIsOffered(): void
    {
        $this->boot(false);

        $this->client->request('GET', '/api/altcha-challenge');

        self::assertSame(204, $this->client->getResponse()->getStatusCode());
    }

    public function testOnTheSpaGetsAFreshChallenge(): void
    {
        $this->boot(true);

        $this->client->request('GET', '/api/altcha-challenge');
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $challenge = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('SHA-256', $challenge['parameters']['algorithm']);
        self::assertNotEmpty($challenge['signature']);
    }

    public function testOnAnInscriptionWithoutSolutionIsRefused(): void
    {
        $this->boot(true);

        $response = $this->postAs('/api/inscriptions', 'application/ld+json', self::inscription());

        self::assertSame(422, $response['status']);
        self::assertSame('altcha', $response['json']['violations'][0]['propertyPath'] ?? null);
    }

    public function testOnAnInscriptionWithItsSolutionGoesOn(): void
    {
        $this->boot(true);

        $response = $this->postAs('/api/inscriptions', 'application/ld+json', [...self::inscription(), 'altcha' => $this->solution()]);

        // Past the challenge: the usual checks answer (no slot was given)
        self::assertSame(422, $response['status']);
        self::assertSame('slotIds', $response['json']['violations'][0]['propertyPath'] ?? null);
    }

    public function testOnALinkRequestWithoutSolutionIsRefused(): void
    {
        $this->boot(true);

        $refused = $this->postAs('/auth/link-request', 'application/json', ['email' => 'marie@example.org']);
        self::assertSame(422, $refused['status']);

        $accepted = $this->postAs('/auth/link-request', 'application/json', ['email' => 'marie@example.org', 'altcha' => $this->solution()]);
        self::assertSame(202, $accepted['status']);
    }

    private function boot(bool $altchaEnabled): void
    {
        $_SERVER['ALTCHA_ENABLED'] = $_ENV['ALTCHA_ENABLED'] = $altchaEnabled ? '1' : '0';
        $this->client = static::createClient();
        $this->client->disableReboot();

        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('DELETE FROM cache_items');
    }

    /**
     * Asks a challenge and solves it, as the widget does in the browser.
     */
    private function solution(): string
    {
        $this->client->request('GET', '/api/altcha-challenge');
        $challenge = Payload::fromArray([
            'challenge' => json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR),
            'solution' => ['counter' => 0, 'derivedKey' => ''],
        ])->challenge;
        $solution = new Altcha()->solveChallenge(new SolveChallengeOptions(algorithm: new Sha(), challenge: $challenge));
        self::assertNotNull($solution);

        return new Payload($challenge, $solution)->toBase64();
    }

    /**
     * @return array<string, mixed>
     */
    private static function inscription(): array
    {
        return ['displayName' => 'Famille Martin', 'email' => 'marie@example.org', 'consent' => true];
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{status: int, json: array<string, mixed>}
     */
    private function postAs(string $url, string $contentType, array $body): array
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => $contentType], json_encode($body, \JSON_THROW_ON_ERROR));
        $response = $this->client->getResponse();

        return [
            'status' => $response->getStatusCode(),
            'json' => (array) json_decode((string) $response->getContent(), true),
        ];
    }
}
