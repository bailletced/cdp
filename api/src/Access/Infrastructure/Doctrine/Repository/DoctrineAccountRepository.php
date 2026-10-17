<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine\Repository;

use App\Access\Domain\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<Account>
 */
final class DoctrineAccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $registry, string $entityClass = Account::class)
    {
        parent::__construct($registry, $entityClass);
    }

    #[\Override]
    public function findById(AccountId $id): ?Account
    {
        return $this->findOneBy(['id.value' => $id->value->toRfc4122()]);
    }

    #[\Override]
    public function findByEmail(Email $email): ?Account
    {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('lower(a.email.value) = lower (:email)')
                ->setParameter('email', $email->value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    #[\Override]
    public function save(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->persist($account);
        $em->flush();
    }

    #[\Override]
    public function remove(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->remove($account);
        $em->flush();
    }

    #[\Override]
    public function existsByEmail(Email $email): bool
    {
        return $this->createQueryBuilder('a')
            ->select('1')
            ->andWhere('lower(a.email.value) = lower (:email)')
            ->setParameter('email', $email->value)
            ->getQuery()
            ->getResult() !== [];
    }
}
