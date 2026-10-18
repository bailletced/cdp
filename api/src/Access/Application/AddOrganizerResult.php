<?php

declare(strict_types=1);

namespace App\Access\Application;

/**
 * Outcome of the AddOrganizer command, returned by the handler to the caller
 * (console command, controller...).
 */
enum AddOrganizerResult
{
    /** A new account was created and made an organizer. */
    case AccountCreated;
    /** An existing account was promoted to organizer. */
    case OrganizerCreated;
    /** The account was already an organizer: nothing was done. */
    case AlreadyOrganizer;
}
