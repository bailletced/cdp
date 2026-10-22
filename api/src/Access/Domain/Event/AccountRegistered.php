<?php

declare(strict_types=1);

namespace App\Access\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Email;

/**
 * Raised when a visitor registers via the landing page (F07-T01). The account
 * is created (or already existed) — a listener sends the personal login link
 * after the transaction commits (transactional outbox via DispatchAfterCurrentBusStamp).
 */
final readonly class AccountRegistered implements DomainEvent
{
    public function __construct(
        public Email $email,
    ) {
    }
}
