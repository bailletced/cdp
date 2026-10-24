<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

/**
 * Grants the device that made inscriptions the read-only « inscrit » view
 * (F10-T02, RG-IN-6). Implemented by the Access BC with the cdp_pass cookie.
 *
 * The inscription ids are plain strings (RFC 4122): Shared must not
 * reference Chain value objects.
 */
interface DevicePassIssuer
{
    /**
     * Adds the inscriptions to the pass of the current device, or gives the
     * device a new pass. The pass expires at the end of the edition + 7 days.
     *
     * @param list<string> $inscriptionIds
     */
    public function grant(array $inscriptionIds, \DateTimeImmutable $editionEndsAt): void;
}
