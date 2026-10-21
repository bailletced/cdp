<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Shared repository contract (in-memory unit test and Doctrine integration
 * test). The base is a KernelTestCase so the Doctrine subclass can boot the
 * kernel from getRepository(); the in-memory subclass never boots it.
 */
abstract class TextRepositoryTestCase extends KernelTestCase
{
    protected TextRepositoryInterface $repository;

    abstract protected function getRepository(): TextRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    /**
     * Hook for Doctrine-backed subclasses: persist (and remove) must reach
     * the database before find* is called. No-op for in-memory repositories.
     */
    protected function flush(): void
    {
    }

    public function testAddAndFind(): void
    {
        $text = $this->createText();

        $this->repository->add($text);
        $this->flush();

        $found = $this->repository->findById($text->getId());
        self::assertNotNull($found);
        self::assertSame($text, $found);
    }

    public function testFindReturnsNullForUnknownId(): void
    {
        $found = $this->repository->findById(new TextId());
        self::assertNull($found);
    }

    public function testFindAll(): void
    {
        $text1 = $this->createText();
        $text2 = $this->createText();

        $this->repository->add($text1);
        $this->repository->add($text2);
        $this->flush();

        $all = $this->repository->findAll();
        self::assertCount(2, $all);
    }

    public function testRemove(): void
    {
        $text = $this->createText();

        $this->repository->add($text);
        $this->flush();
        self::assertNotNull($this->repository->findById($text->getId()));

        $this->repository->remove($text);
        $this->flush();
        self::assertNull($this->repository->findById($text->getId()));
    }

    protected function createText(): Text
    {
        return Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila, Le Château intérieur, I, 1'),
            new RichText('<p>Texte à méditer.</p>'),
        );
    }
}
