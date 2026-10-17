<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Repository;

use App\Access\Domain\Organizer;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * In-memory implementation of OrganizerRepositoryInterface for testing purposes.
 */
final class InMemoryOrganizerRepository implements OrganizerRepositoryInterface
{
    /**
     * @var array<string, Organizer> Keyed by Account UUID string representation
     */
    private array $organizersByAccountId = [];

    /**
     * @var Organizer[]
     */
    private array $organizers = [];

    #[\Override]
    public function findByAccountId(Uuid $accountId): ?Organizer
    {
        return $this->organizersByAccountId[$accountId->toRfc4122()] ?? null;
    }

    #[\Override]
    public function findAll(): array
    {
        return $this->organizers;
    }

    #[\Override]
    public function isOrganizer(Uuid $accountId): bool
    {
        return isset($this->organizersByAccountId[$accountId->toRfc4122()]);
    }

    #[\Override]
    public function save(Organizer $organizer): void
    {
        $accountId = $organizer->getAccountId()->toRfc4122();
        $this->organizersByAccountId[$accountId] = $organizer;
        $this->organizers[$accountId] = $organizer;
    }

    #[\Override]
    public function remove(Organizer $organizer): void
    {
        $accountId = $organizer->getAccountId()->toRfc4122();
        unset($this->organizersByAccountId[$accountId]);
        unset($this->organizers[$accountId]);
    }

    #[\Override]
    public function countAll(): int
    {
        return \count($this->organizers);
    }
}
