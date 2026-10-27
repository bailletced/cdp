<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * F15-T02 (§6.7): behind Traefik, the client IP the anti-abuse limiters
 * count comes from X-Forwarded-For — but only when the request comes from a
 * private network address (Traefik, on the Swarm overlay). A client reaching
 * the app another way cannot choose the IP it is counted under.
 */
final class TrustedProxiesTest extends KernelTestCase
{
    protected function setUp(): void
    {
        // The framework applies the trusted proxies when the kernel boots
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Request::setTrustedProxies([], -1);
    }

    public function testTheClientIpComesFromTraefik(): void
    {
        $request = Request::create('/api/inscriptions', 'POST', server: [
            'REMOTE_ADDR' => '10.0.1.12',
            'HTTP_X_FORWARDED_FOR' => '82.64.12.34',
        ]);

        self::assertSame('82.64.12.34', $request->getClientIp());
    }

    public function testAForwardedHeaderFromOutsideIsIgnored(): void
    {
        $request = Request::create('/api/inscriptions', 'POST', server: [
            'REMOTE_ADDR' => '91.198.174.192',
            'HTTP_X_FORWARDED_FOR' => '82.64.12.34',
        ]);

        self::assertSame('91.198.174.192', $request->getClientIp());
    }
}
