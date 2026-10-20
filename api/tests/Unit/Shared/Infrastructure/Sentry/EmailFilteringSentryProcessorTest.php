<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Sentry;

use App\Shared\Infrastructure\Sentry\EmailFilteringSentryProcessor;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\UserDataBag;

/**
 * Unit tests for EmailFilteringSentryProcessor (F03-T19).
 * Done when: an event containing an email is filtered before being sent to Sentry.
 */
final class EmailFilteringSentryProcessorTest extends TestCase
{
    private EmailFilteringSentryProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new EmailFilteringSentryProcessor();
    }

    public function testItFiltersEmailFromUserData(): void
    {
        $event = $this->createEvent();
        $event->setUser(UserDataBag::createFromArray([
            'email' => 'priant@bailletced.org',
            'id' => '123',
        ]));

        $filtered = ($this->processor)($event);

        $this->assertNotNull($filtered);
        $user = $filtered->getUser();
        $this->assertNotNull($user);
        $this->assertNull($user->getEmail());
        $this->assertSame('123', $user->getId());
    }

    public function testItFiltersEmailFromExtraData(): void
    {
        $event = $this->createEvent();
        $event->setExtra(['contact' => 'marie@example.org', 'name' => 'Marie']);

        $filtered = ($this->processor)($event);

        $extra = $filtered->getExtra();
        $this->assertSame('[FILTERED]', $extra['contact'] ?? null);
        $this->assertSame('Marie', $extra['name'] ?? null);
    }

    public function testItFiltersEmailFromTags(): void
    {
        $event = $this->createEvent();
        $event->setTags(['email' => 'thomas@bailletced.org', 'env' => 'prod']);

        $filtered = ($this->processor)($event);

        $tags = $filtered->getTags();
        $this->assertSame('[FILTERED]', $tags['email'] ?? null);
        $this->assertSame('prod', $tags['env'] ?? null);
    }

    public function testItFiltersEmailFromBreadcrumbs(): void
    {
        $event = $this->createEvent();
        $event->setExtra(['breadcrumbs' => [
            ['message' => 'User priant@bailletced.org logged in'],
        ]]);

        $filtered = ($this->processor)($event);

        $extra = $filtered->getExtra();
        $this->assertStringNotContainsString(
            'priant@bailletced.org',
            json_encode($extra) ?: ''
        );
    }

    public function testItFiltersEmailFromExceptionMessage(): void
    {
        $event = $this->createEvent();
        $exception = new \Sentry\ExceptionDataBag(
            new \RuntimeException('Failed to send email to priant@bailletced.org')
        );
        $event->setExceptions([$exception]);

        $filtered = ($this->processor)($event);

        $exceptions = $filtered->getExceptions();
        $this->assertStringNotContainsString(
            'priant@bailletced.org',
            $exceptions[0]->getValue() ?? ''
        );
    }

    public function testItReturnsEventUnchangedWhenNoEmail(): void
    {
        $event = $this->createEvent();
        $event->setUser(UserDataBag::createFromArray(['id' => '123']));
        $event->setExtra(['name' => 'Marie']);

        $filtered = ($this->processor)($event);

        $user = $filtered->getUser();
        $this->assertNotNull($user);
        $this->assertSame('123', $user->getId());
        $this->assertSame('Marie', $filtered->getExtra()['name'] ?? null);
    }

    private function createEvent(): Event
    {
        return Event::createEvent();
    }
}
