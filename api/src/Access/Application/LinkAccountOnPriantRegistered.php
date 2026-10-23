<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Event\AsEventListener;
use App\Shared\Application\Port\PriantDirectory;
use App\Shared\Domain\Event\Integration\PriantRegistered;

/**
 * Links the account to the priant when a new priant is registered with an
 * email (F09-T09). Runs after the command transaction commits (transactional
 * outbox), so the priant is guaranteed to be persisted when the account is
 * linked.
 *
 * A priant without email (created by an organizer, F16) has no account to
 * link: the event is a no-op. When an organizer registers on the site with
 * the email of their existing account, that account is linked — a single
 * account is kept (RG-IN-2: one email = one priant).
 */
#[AsEventListener]
final readonly class LinkAccountOnPriantRegistered
{
    public function __construct(
        private PriantDirectory $priantDirectory,
    ) {
    }

    public function __invoke(PriantRegistered $event): void
    {
        if (null === $event->email) {
            return;
        }

        $this->priantDirectory->linkAccountToPriant($event->email, $event->priantId);
    }
}
