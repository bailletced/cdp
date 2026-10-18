<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Email;

/**
 * Adds an organizer for the given email address.
 * Idempotent: an account that is already an organizer is left untouched.
 */
final readonly class AddOrganizer implements CommandInterface
{
    public function __construct(
        public Email $email,
    ) {
    }
}
