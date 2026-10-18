<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\RawMessage;

/**
 * Fake mailer transport for functional tests: records every sent message in
 * memory instead of delivering it, so tests can assert on the emails (F02-T14).
 *
 * It extends AbstractTransport so the message event is dispatched like with a
 * real transport: the TemplatedEmail templates are rendered before the capture.
 */
final class InMemoryMailTransport extends AbstractTransport
{
    /** @var list<RawMessage> */
    public array $sent = [];

    public function __construct(?EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($dispatcher);
    }

    public function reset(): void
    {
        $this->sent = [];
    }

    public function __toString(): string
    {
        return 'in-memory://default';
    }

    protected function doSend(SentMessage $message): void
    {
        $this->sent[] = $message->getOriginalMessage();
    }
}
