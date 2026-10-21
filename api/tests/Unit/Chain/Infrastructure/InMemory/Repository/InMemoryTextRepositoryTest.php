<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Tests\Unit\Chain\Domain\Repository\TextRepositoryTestCase;

final class InMemoryTextRepositoryTest extends TextRepositoryTestCase
{
    protected function getRepository(): TextRepositoryInterface
    {
        return new InMemoryTextRepository();
    }
}
