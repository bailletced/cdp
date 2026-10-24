<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

/**
 * Tells which inscriptions still exist (F10-T04). Implemented by the Chain
 * BC, so Access can recompute the rights of a device pass on each request:
 * a cancelled inscription gives nothing anymore (specs-techniques §6.4).
 *
 * The inscription ids are plain strings (RFC 4122): Shared must not
 * reference Chain value objects.
 */
interface InscriptionDirectory
{
    /**
     * @param list<string> $inscriptionIds
     *
     * @return list<string> The given ids whose inscription is still active, in the given order
     */
    public function filterActive(array $inscriptionIds): array;
}
