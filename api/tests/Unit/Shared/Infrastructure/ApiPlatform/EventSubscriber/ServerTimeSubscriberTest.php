<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ApiPlatform\EventSubscriber;

use App\Shared\Infrastructure\ApiPlatform\EventSubscriber\ServerTimeSubscriber;
use App\Shared\Infrastructure\Clock\MockClock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test pour F00-T17 : En-tête X-Server-Time.
 */
final class ServerTimeSubscriberTest extends TestCase
{
    private ServerTimeSubscriber $subscriber;

    protected function setUp(): void
    {
        $clock = new MockClock(1700000000);
        $this->subscriber = new ServerTimeSubscriber($clock);
    }

    public function testItSubscribesToKernelResponse(): void
    {
        $events = ServerTimeSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.response', $events);
        // La valeur peut être un tableau [method, priority] ou juste la méthode
        if (\is_array($events['kernel.response'])) {
            $this->assertSame('addServerTimeHeader', $events['kernel.response'][0]);
        } else {
            $this->assertSame('addServerTimeHeader', $events['kernel.response']);
        }
    }

    public function testItAddsServerTimeHeader(): void
    {
        $response = new Response();
        $event = $this->createResponseEvent($response);

        $this->subscriber->addServerTimeHeader($event);

        $this->assertTrue($response->headers->has('X-Server-Time'));
        $this->assertSame('1700000000', $response->headers->get('X-Server-Time'));
    }

    /**
     * @return ResponseEvent&object
     */
    private function createResponseEvent(Response $response): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        
        return new ResponseEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
    }
}
