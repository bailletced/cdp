<?php

declare(strict_types=1);

namespace App\Access\Domain\Repository;

use App\Access\Domain\Organizer;
use Symfony\Component\Uid\Uuid;

/**
 * @template-extends RepositoryInterface<Organizer>
 */
interface OrganizerRepositoryInterface
{
    /**
     * Find an organizer by Account UUID.
     */
    public function findByAccountId(Uuid $accountId): ?Organizer;

    /**
     * Find all organizers.
     *
     * @return Organizer[]
     */
    public function findAll(): array;

    /**
     * Check if an account is an organizer.
     */
    public function isOrganizer(Uuid $accountId): bool;

    /**
     * Add an organizer to the repository. The aggregate is persisted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function add(Organizer $organizer): void;

    /**
     * Remove an organizer from the repository. The aggregate is deleted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function remove(Organizer $organizer): void;

    /**
     * Get the count of organizers.
     */
    public function countAll(): int;
}
