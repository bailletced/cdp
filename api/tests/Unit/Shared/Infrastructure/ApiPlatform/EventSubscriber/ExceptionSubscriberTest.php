<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ApiPlatform\EventSubscriber;

use App\Shared\Infrastructure\ApiPlatform\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test pour F00-T16 : Normalisation des erreurs.
 */
final class ExceptionSubscriberTest extends TestCase
{
    private ExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new ExceptionSubscriber();
    }

    public function testItSubscribesToKernelEvents(): void
    {
        $events = ExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.response', $events);
        $this->assertArrayHasKey('kernel.exception', $events);
    }

    public function testItAddsCacheControlHeader(): void
    {
        $response = new Response();
        $event = $this->createResponseEvent($response);

        $this->subscriber->addCacheControlHeader($event);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function testItDoesNotOverrideExistingCacheControl(): void
    {
        $response = new Response();
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        $event = $this->createResponseEvent($response);

        $this->subscriber->addCacheControlHeader($event);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
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
