<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\RateLimiter;

use App\Shared\Infrastructure\Symfony\Kernel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * F15-T01 (§6.7, T18): the limiters of the public forms keep their counters
 * in PostgreSQL, so that every replica of the application sees the same
 * counts.
 */
final class RateLimitersTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('DELETE FROM cache_items');
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function limits(): iterable
    {
        yield 'inscription, per IP' => ['test.limiter.inscription_by_ip', 10];
        yield 'inscription, per e-mail' => ['test.limiter.inscription_by_email', 5];
        yield 'link request, per IP' => ['test.limiter.link_request_by_ip', 10];
        yield 'link request, per e-mail' => ['test.limiter.link_request_by_email', 3];
    }

    #[DataProvider('limits')]
    public function testEachFormHasItsHourlyLimit(string $limiter, int $limit): void
    {
        $factory = self::getContainer()->get($limiter);
        \assert($factory instanceof RateLimiterFactoryInterface);

        for ($i = 1; $i <= $limit; ++$i) {
            self::assertTrue($factory->create('203.0.113.7')->consume()->isAccepted(), \sprintf('Attempt %d is accepted.', $i));
        }

        $refused = $factory->create('203.0.113.7')->consume();
        self::assertFalse($refused->isAccepted());
        // Within the hour
        self::assertLessThanOrEqual(3600, $refused->getRetryAfter()->getTimestamp() - time());
        // Another key keeps its own counter
        self::assertTrue($factory->create('198.51.100.1')->consume()->isAccepted());
    }

    public function testTheCountersAreSharedBetweenTwoReplicas(): void
    {
        $here = self::getContainer()->get('test.limiter.link_request_by_email');
        \assert($here instanceof RateLimiterFactoryInterface);

        // Another replica: its own kernel, its own container, the same database
        $replica = new Kernel('test', (bool) $_SERVER['APP_DEBUG']);
        $replica->boot();
        $elsewhere = $replica->getContainer()->get('test.limiter.link_request_by_email');
        \assert($elsewhere instanceof RateLimiterFactoryInterface);

        for ($i = 0; $i < 3; ++$i) {
            $here->create('marie@example.org')->consume();
        }

        self::assertFalse($elsewhere->create('marie@example.org')->consume()->isAccepted());
        $replica->shutdown();
    }
}
