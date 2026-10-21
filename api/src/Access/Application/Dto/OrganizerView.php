<?php

declare(strict_types=1);

namespace App\Access\Application\Dto;

/**
 * Read model of an organizer for the admin back-office (F04-T02).
 * Exposes the email (only under /api/admin/*) and who added the organizer.
 */
final readonly class OrganizerView
{
    public function __construct(
        public string $id,
        public string $email,
        public string $addedByEmail,
        public string $addedAt,
    ) {
    }
}
