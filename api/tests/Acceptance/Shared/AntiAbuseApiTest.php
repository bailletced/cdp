<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Shared\Application\Message\LinkMessage;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\ApiTestCase;
use App\Tests\Factory\AccountFactory;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * F15-T03 (§6.7, US-15.2): the public forms are limited per hour — the
 * inscription to 10 per IP and 5 per e-mail, the link request to 10 per IP
 * and 3 per e-mail. Beyond, the inscription answers 429 with Retry-After;
 * the link request answers 429 per IP, but stays a silent 202 per e-mail so
 * that it never tells whether an address is known.
 */
final class AntiAbuseApiTest extends ApiTestCase
{
    use InteractsWithMessenger;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('DELETE FROM cache_items');
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');
    }

    public function testTheEleventhInscriptionOfTheHourFromAnIpIsRefused(): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            // Invalid on purpose: every attempt counts, valid or not
            self::assertSame(422, $this->inscription('82.64.12.34', ['email' => \sprintf('priant%d@example.org', $i)])['status']);
        }

        $refused = $this->inscription('82.64.12.34', ['email' => 'priant11@example.org']);

        self::assertSame(429, $refused['status']);
        $retryAfter = (int) ($refused['headers']['retry-after'][0] ?? 0);
        self::assertGreaterThan(0, $retryAfter);
        self::assertLessThanOrEqual(3600, $retryAfter);
        // Another visitor is not affected
        self::assertSame(422, $this->inscription('91.198.174.192', ['email' => 'autre@example.org'])['status']);
    }

    public function testTheSixthInscriptionOfTheHourForAnEmailIsRefused(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            self::assertSame(422, $this->inscription(\sprintf('82.64.12.%d', $i), ['email' => 'marie@example.org'])['status']);
        }

        // From yet another IP, with the same address written differently
        $refused = $this->inscription('82.64.12.6', ['email' => ' Marie@Example.org ']);

        self::assertSame(429, $refused['status']);
        self::assertArrayHasKey('retry-after', $refused['headers']);
    }

    public function testTheEleventhLinkRequestOfTheHourFromAnIpIsRefused(): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            self::assertSame(202, $this->linkRequest('82.64.12.34', \sprintf('priant%d@example.org', $i))['status']);
        }

        $refused = $this->linkRequest('82.64.12.34', 'priant11@example.org');

        self::assertSame(429, $refused['status']);
        self::assertArrayHasKey('retry-after', $refused['headers']);
    }

    public function testTheFourthLinkRequestOfTheHourForAnEmailStaysSilent(): void
    {
        AccountFactory::createOne(['email' => new Email('marie@example.org')]);

        for ($i = 1; $i <= 3; ++$i) {
            self::assertSame(202, $this->linkRequest(\sprintf('82.64.12.%d', $i), 'marie@example.org')['status']);
        }
        self::assertCount(3, $this->transport('async_transactional')->queue()->messages(LinkMessage::class));

        $fourth = $this->linkRequest('82.64.12.4', 'marie@example.org');

        // The same answer as ever — but no e-mail leaves
        self::assertSame(202, $fourth['status']);
        self::assertSame(
            ['message' => 'Si un compte correspond à cette adresse, un lien de connexion vient de vous être envoyé.'],
            json_decode($fourth['content'], true, 512, \JSON_THROW_ON_ERROR),
        );
        self::assertCount(3, $this->transport('async_transactional')->queue()->messages(LinkMessage::class));
    }

    /**
     * An inscription attempt without slots: refused (422) once counted.
     *
     * @param array<string, mixed> $body
     *
     * @return array{status: int, content: string, headers: array<string, list<string|null>>}
     */
    private function inscription(string $ip, array $body): array
    {
        return $this->send('/api/inscriptions', 'application/ld+json', $ip, ['displayName' => 'Famille Martin', 'consent' => true, ...$body]);
    }

    /**
     * @return array{status: int, content: string, headers: array<string, list<string|null>>}
     */
    private function linkRequest(string $ip, string $email): array
    {
        return $this->send('/auth/link-request', 'application/json', $ip, ['email' => $email]);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{status: int, content: string, headers: array<string, list<string|null>>}
     */
    private function send(string $url, string $contentType, string $ip, array $body): array
    {
        $this->client->request('POST', $url, [], [], [
            'CONTENT_TYPE' => $contentType,
            'REMOTE_ADDR' => $ip,
        ], json_encode($body, \JSON_THROW_ON_ERROR));
        $response = $this->client->getResponse();

        return [
            'status' => $response->getStatusCode(),
            'content' => (string) $response->getContent(),
            'headers' => $response->headers->all(),
        ];
    }
}
