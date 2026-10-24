<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\InMemory\Repository;

use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\DevicePassRepositoryInterface;

/**
 * In-memory implementation of DevicePassRepositoryInterface for testing purposes.
 */
final class InMemoryDevicePassRepository implements DevicePassRepositoryInterface
{
    /**
     * @var array<string, DevicePass> Keyed by token hash
     */
    private array $passes = [];

    #[\Override]
    public function add(DevicePass $pass): void
    {
        $this->passes[$pass->getTokenHash()] = $pass;
    }

    #[\Override]
    public function findByToken(#[\SensitiveParameter] string $token): ?DevicePass
    {
        return $this->passes[DevicePass::hashToken($token)] ?? null;
    }
}
