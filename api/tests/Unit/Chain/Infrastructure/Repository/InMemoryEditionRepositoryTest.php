<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Repository;

use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Infrastructure\Repository\InMemoryEditionRepository;
use App\Tests\Unit\Chain\Domain\Repository\EditionRepositoryTestCase;

final class InMemoryEditionRepositoryTest extends EditionRepositoryTestCase
{
    protected function getRepository(): EditionRepositoryInterface
    {
        return new InMemoryEditionRepository();
    }
}
