<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event\Integration;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Email;

/**
 * Integration event raised when a NEW priant is created by a self-service
 * registration (F09-T07). It lives in Shared (not in the Chain BC) so the
 * Access BC can listen to it without depending on Chain: a listener links the
 * matching account to the priant (F09-T09).
 *
 * The priant id is a plain string: Shared must not reference Chain value
 * objects. The email is null for priants created without email.
 */
final readonly class PriantRegistered implements DomainEvent
{
    public function __construct(
        public string $priantId,
        public ?Email $email,
    ) {
    }
}
