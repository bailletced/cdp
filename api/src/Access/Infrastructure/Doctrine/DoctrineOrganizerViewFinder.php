<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Doctrine;

use App\Access\Application\Dto\OrganizerView;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Access\Domain\Entity\Account;
use App\Access\Domain\Entity\Organizer;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine implementation of OrganizerViewFinder (F04-T02).
 * Joins Organizer with Account (for the email) and a second Account alias
 * (for the addedBy email).
 */
final readonly class DoctrineOrganizerViewFinder implements OrganizerViewFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return OrganizerView[]
     */
    public function findAll(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('o.id.value as organizerId, a.email.value as email, ab.email.value as addedByEmail, o.addedAt as addedAt')
            ->from(Organizer::class, 'o')
            ->innerJoin(Account::class, 'a', 'WITH', 'a.id.value = o.accountId')
            ->innerJoin(Account::class, 'ab', 'WITH', 'ab.id.value = o.addedBy')
            ->orderBy('o.addedAt', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(string $organizerId): ?OrganizerView
    {
        /** @var array<string, mixed>|null $row */
        $row = $this->entityManager->createQueryBuilder()
            ->select('o.id.value as organizerId, a.email.value as email, ab.email.value as addedByEmail, o.addedAt as addedAt')
            ->from(Organizer::class, 'o')
            ->innerJoin(Account::class, 'a', 'WITH', 'a.id.value = o.accountId')
            ->innerJoin(Account::class, 'ab', 'WITH', 'ab.id.value = o.addedBy')
            ->andWhere('o.id.value = :id')
            ->setParameter('id', $organizerId)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByEmail(Email $email): ?OrganizerView
    {
        /** @var array<string, mixed>|null $row */
        $row = $this->entityManager->createQueryBuilder()
            ->select('o.id.value as organizerId, a.email.value as email, ab.email.value as addedByEmail, o.addedAt as addedAt')
            ->from(Organizer::class, 'o')
            ->innerJoin(Account::class, 'a', 'WITH', 'a.id.value = o.accountId')
            ->innerJoin(Account::class, 'ab', 'WITH', 'ab.id.value = o.addedBy')
            ->andWhere('lower(a.email.value) = lower(:email)')
            ->setParameter('email', $email->value)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): OrganizerView
    {
        /** @var \Symfony\Component\Uid\Uuid $organizerId */
        $organizerId = $row['organizerId'];
        /** @var string $email */
        $email = $row['email'];
        /** @var string $addedByEmail */
        $addedByEmail = $row['addedByEmail'];
        /** @var \DateTimeImmutable $addedAt */
        $addedAt = $row['addedAt'];

        return new OrganizerView(
            id: $organizerId->toRfc4122(),
            email: $email,
            addedByEmail: $addedByEmail,
            addedAt: $addedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
