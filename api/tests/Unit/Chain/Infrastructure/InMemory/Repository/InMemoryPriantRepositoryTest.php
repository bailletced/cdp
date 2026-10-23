<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Tests\Unit\Chain\Domain\Repository\PriantRepositoryTestCase;

final class InMemoryPriantRepositoryTest extends PriantRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryPriantRepository
    {
        return new InMemoryPriantRepository();
    }
}
