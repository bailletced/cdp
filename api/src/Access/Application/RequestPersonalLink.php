<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Email;

/**
 * Requests a personal (passwordless) login link for the account matching the
 * given email. The handler stays silent when no account matches, so the
 * endpoint cannot be used to enumerate accounts (F02-T14).
 */
final readonly class RequestPersonalLink implements CommandInterface
{
    public function __construct(
        public Email $email,
    ) {
    }
}
