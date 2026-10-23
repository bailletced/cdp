<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

use App\Shared\Domain\ValueObject\Email;

/**
 * Links an account to a priant (F09-T09). Implemented by the Access BC: the
 * account is found by email (case-insensitive) and its priant id is set, or
 * a new account is created when none exists for this email yet.
 *
 * The priant id is a plain string (RFC 4122): Shared must not reference
 * Chain value objects.
 */
interface PriantDirectory
{
    /**
     * Link the account matching the email to the given priant. Creates the
     * account when no account exists for this email yet.
     */
    public function linkAccountToPriant(Email $email, string $priantId): void;
}
