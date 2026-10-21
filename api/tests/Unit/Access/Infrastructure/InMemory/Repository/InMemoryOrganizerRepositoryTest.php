<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\InMemory\Repository;

use App\Access\Infrastructure\InMemory\Repository\InMemoryOrganizerRepository;
use App\Tests\Unit\Access\Domain\Repository\OrganizerRepositoryTestCase;

final class InMemoryOrganizerRepositoryTest extends OrganizerRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryOrganizerRepository
    {
        return new InMemoryOrganizerRepository();
    }
}
