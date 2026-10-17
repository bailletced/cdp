<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Repository;

use App\Access\Infrastructure\Repository\InMemoryOrganizerRepository;
use App\Tests\Unit\Access\Domain\Repository\OrganizerRepositoryTestCase;

final class InMemoryOrganizerRepositoryTest extends OrganizerRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryOrganizerRepository
    {
        return new InMemoryOrganizerRepository();
    }
}
