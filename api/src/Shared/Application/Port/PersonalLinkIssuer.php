<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

use App\Shared\Application\Dto\PersonalLink;
use App\Shared\Domain\ValueObject\Email;

/**
 * Issues personal (passwordless) login links for accounts (F02-T11).
 */
interface PersonalLinkIssuer
{
    /**
     * Issue a personal login link for an account.
     *
     * Lifetime policy (arbitrage Q8):
     * - during an edition (up to its end + 7 days): the link expires at edition end + 7 days
     * - otherwise (between editions): 7 days
     * - organizer accounts: always 7 days
     *
     * @param string                  $accountId     Account UUID (RFC 4122)
     * @param \DateTimeImmutable|null $editionEndsAt End of the edition the link is issued for; null when issued outside an edition
     */
    public function issue(string $accountId, ?\DateTimeImmutable $editionEndsAt = null): PersonalLink;

    /**
     * Issue the personal link of the account of an email (F11-T05), same
     * lifetime policy as issue(). A priant and its account share their email
     * (RG-IN-2).
     *
     * @throws \RuntimeException when no account exists for this email yet (it is
     *                           linked asynchronously: the caller retries later)
     */
    public function issueForEmail(Email $email, ?\DateTimeImmutable $editionEndsAt = null): PersonalLink;
}
