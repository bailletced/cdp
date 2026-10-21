<?php

declare(strict_types=1);

namespace App\Access\Application\Query;

use App\Access\Application\Dto\OrganizerView;
use App\Shared\Domain\ValueObject\Email;

/**
 * Port: read access to the organizers list for the admin back-office (F04-T02).
 */
interface OrganizerViewFinder
{
    /**
     * @return OrganizerView[]
     */
    public function findAll(): array;

    public function findById(string $organizerId): ?OrganizerView;

    public function findByEmail(Email $email): ?OrganizerView;
}
