<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine\Repository;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;

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
            /** @var Account|null $account */
            $account = $this->createQueryBuilder('a')
                ->andWhere('lower(a.email.value) = lower (:email)')
                ->setParameter('email', $email->value)
                ->getQuery()
                ->getOneOrNullResult();

            return $account;
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    #[\Override]
    public function add(Account $account): void
    {
        $this->getEntityManager()->persist($account);
    }

    #[\Override]
    public function remove(Account $account): void
    {
        $this->getEntityManager()->remove($account);
    }

    #[\Override]
    public function existsByEmail(Email $email): bool
    {
        return [] !== $this->createQueryBuilder('a')
            ->select('1')
            ->andWhere('lower(a.email.value) = lower (:email)')
            ->setParameter('email', $email->value)
            ->getQuery()
            ->getResult();
    }
}
