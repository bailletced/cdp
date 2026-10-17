<?php

declare(strict_types=1);

namespace App\Tests\Factory\Access;

use App\Access\Domain\Organizer;
use Symfony\Component\Uid\Uuid;

/**
 * Factory for creating Organizer instances for testing.
 * Use this in integration and functional tests where you need real Organizer objects.
 */
final class OrganizerFactory
{
    public static function create(
        ?Uuid $accountId = null,
        ?Uuid $addedBy = null,
        ?\DateTimeImmutable $addedAt = null
    ): Organizer {
        $accountId = $accountId ?? Uuid::v7();
        $addedBy = $addedBy ?? Uuid::v7();
        $addedAt = $addedAt ?? new \DateTimeImmutable();

        return new Organizer(
            $accountId,
            $addedBy,
            $addedAt
        );
    }
}
