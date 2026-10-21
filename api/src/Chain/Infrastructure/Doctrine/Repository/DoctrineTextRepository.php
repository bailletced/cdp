<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine\Repository;

use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\TextId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template-extends ServiceEntityRepository<Text>
 *
 * @method Text|null find($id, $lockMode = null, $lockVersion = null)
 * @method Text|null findOneBy(array<string, mixed> $criteria)
 * @method Text[]    findAll()
 */
final class DoctrineTextRepository extends ServiceEntityRepository implements TextRepositoryInterface
{
    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $registry, string $entityClass = Text::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function findById(TextId $id): ?Text
    {
        return $this->findOneBy(['id.value' => $id->value->toRfc4122()]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function add(Text $text): void
    {
        $this->getEntityManager()->persist($text);
    }

    public function remove(Text $text): void
    {
        $this->getEntityManager()->remove($text);
    }
}
