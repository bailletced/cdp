<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\InMemory\Repository;

use App\Access\Infrastructure\InMemory\Repository\InMemoryDevicePassRepository;
use App\Tests\Unit\Access\Domain\Repository\DevicePassRepositoryTestCase;

final class InMemoryDevicePassRepositoryTest extends DevicePassRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryDevicePassRepository
    {
        return new InMemoryDevicePassRepository();
    }
}
