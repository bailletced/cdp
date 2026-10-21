<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineEditionRepository;
use App\Tests\Unit\Chain\Domain\Repository\EditionRepositoryTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEditionRepositoryTest extends EditionRepositoryTestCase
{
    private EntityManagerInterface $em;

    protected function getRepository(): EditionRepositoryInterface
    {
        self::bootKernel();

        $registry = self::getContainer()->get('doctrine');
        $this->em = $registry->getManager();
        $repository = new DoctrineEditionRepository($registry, Edition::class);

        // Clean slate for each test
        foreach ($this->em->getConnection()->fetchAllAssociative('SELECT id FROM edition') as $row) {
            $this->em->getConnection()->executeStatement('DELETE FROM slot WHERE edition_id = ?', [$row['id']]);
            $this->em->getConnection()->executeStatement('DELETE FROM edition WHERE id = ?', [$row['id']]);
        }
        $this->em->clear();

        return $repository;
    }

    protected function flush(): void
    {
        $this->em->flush();
    }
}
