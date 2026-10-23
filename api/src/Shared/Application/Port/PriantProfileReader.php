<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

use App\Shared\Application\Dto\PriantProfile;

/**
 * Reads the profile of a priant (F09-T14). Implemented by the Chain BC:
 * the priant aggregate lives there, but the Access BC (GET /api/me) may
 * only depend on Shared.
 *
 * The priant id is a plain string (RFC 4122): Shared must not reference
 * Chain value objects.
 */
interface PriantProfileReader
{
    /**
     * The profile of the priant with the given id, or null when no priant
     * exists for this id.
     */
    public function findProfileByPriantId(string $priantId): ?PriantProfile;
}
