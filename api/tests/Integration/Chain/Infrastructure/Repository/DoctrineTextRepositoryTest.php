<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Repository;

use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineTextRepository;
use App\Tests\Unit\Chain\Domain\Repository\TextRepositoryTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTextRepositoryTest extends TextRepositoryTestCase
{
    private EntityManagerInterface $em;

    protected function getRepository(): TextRepositoryInterface
    {
        self::bootKernel();

        $registry = self::getContainer()->get('doctrine');
        $this->em = $registry->getManager();
        $repository = new DoctrineTextRepository($registry, Text::class);

        // Clean slate for each test (slot.text_id has no FK: texts can be deleted freely)
        $this->em->getConnection()->executeStatement('DELETE FROM text');
        $this->em->clear();

        return $repository;
    }

    protected function flush(): void
    {
        $this->em->flush();
    }
}
