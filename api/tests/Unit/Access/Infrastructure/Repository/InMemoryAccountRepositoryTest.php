<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Repository;

use App\Access\Infrastructure\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Access\Domain\Repository\AccountRepositoryTestCase;

final class InMemoryAccountRepositoryTest extends AccountRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryAccountRepository
    {
        return new InMemoryAccountRepository();
    }
}
