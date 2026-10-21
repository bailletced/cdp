<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineEditionRepository;
use App\Tests\Unit\Chain\Domain\Repository\EditionRepositoryTestCase;

final class DoctrineEditionRepositoryTest extends EditionRepositoryTestCase
{
    protected function getRepository(): EditionRepositoryInterface
    {
        $registry = self::getContainer()->get('doctrine');
        $repository = new DoctrineEditionRepository($registry, Edition::class);

        // Clean slate for each test
        $em = $registry->getManager();
        foreach ($em->getConnection()->fetchAllAssociative('SELECT id FROM edition') as $row) {
            $em->getConnection()->executeStatement('DELETE FROM slot WHERE edition_id = ?', [$row['id']]);
            $em->getConnection()->executeStatement('DELETE FROM edition WHERE id = ?', [$row['id']]);
        }
        $em->clear();

        return $repository;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Persist and flush after each add so that the repository can find the entity
        $this->repository = $this->getRepository();
    }
}
