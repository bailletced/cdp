<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Tests\Unit\Chain\Domain\Repository\InscriptionRepositoryTestCase;

final class InMemoryInscriptionRepositoryTest extends InscriptionRepositoryTestCase
{
    #[\Override]
    protected function getRepository(): InMemoryInscriptionRepository
    {
        return new InMemoryInscriptionRepository();
    }
}
