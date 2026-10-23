<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Event\Fixture;

use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Fake event bus: records every dispatched event, in order.
 */
final class EventBusSpy implements EventBusInterface
{
    /**
     * @var list<DomainEvent>
     */
    public array $dispatched = [];

    #[\Override]
    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }
}
