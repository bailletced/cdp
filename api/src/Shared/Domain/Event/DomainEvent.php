<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Marker interface for domain events: persisted in the async_transactional
 * outbox and consumed by the worker after the command transaction commits.
 */
interface DomainEvent
{
}
