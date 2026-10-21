<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\InMemory\Repository;

use App\Access\Infrastructure\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Access\Domain\Repository\AccountRepositoryTestCase;

final class InMemoryAccountRepositoryTest extends AccountRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryAccountRepository
    {
        return new InMemoryAccountRepository();
    }
}
