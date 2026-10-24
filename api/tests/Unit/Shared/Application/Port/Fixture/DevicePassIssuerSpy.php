<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Port\Fixture;

use App\Shared\Application\Port\DevicePassIssuer;

/**
 * Device pass issuer spy: records every grant.
 */
final class DevicePassIssuerSpy implements DevicePassIssuer
{
    /**
     * @var list<array{inscriptionIds: list<string>, editionEndsAt: \DateTimeImmutable}>
     */
    public array $granted = [];

    #[\Override]
    public function grant(array $inscriptionIds, \DateTimeImmutable $editionEndsAt): void
    {
        $this->granted[] = ['inscriptionIds' => $inscriptionIds, 'editionEndsAt' => $editionEndsAt];
    }
}
