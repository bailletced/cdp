<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Port\Fixture;

use App\Shared\Application\Port\InscriptionDirectory;

/**
 * Fake inscription directory: only the given inscriptions are active.
 */
final class FakeInscriptionDirectory implements InscriptionDirectory
{
    /**
     * @param list<string> $active
     */
    public function __construct(
        private readonly array $active = [],
    ) {
    }

    #[\Override]
    public function filterActive(array $inscriptionIds): array
    {
        return array_values(array_intersect($inscriptionIds, $this->active));
    }
}
