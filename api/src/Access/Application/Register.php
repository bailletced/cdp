<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Email;

/**
 * Registers a new account for the given email and sends a personal login
 * link. If an account already exists for this email, only the link is sent
 * (the operation is idempotent). The endpoint must never reveal whether the
 * account was created or already existed (F07-T01).
 */
final readonly class Register implements CommandInterface
{
    public function __construct(
        public Email $email,
    ) {
    }
}
