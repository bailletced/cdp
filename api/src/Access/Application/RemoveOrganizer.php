<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Email;

/**
 * Removes the organizer role for the given email address.
 * Refuses when the target is the last remaining organizer.
 */
final readonly class RemoveOrganizer implements CommandInterface
{
    public function __construct(
        public Email $email,
    ) {
    }
}
