<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\AdminInscriptionView;
use App\Chain\Application\Query\AdminInscriptionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\Time\DayLabel;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Doctrine implementation of AdminInscriptionViewFinder (F16-T03).
 *
 * Reads the inscriptions of an edition with the display name and the e-mail
 * of their priant — served under /api/admin only (T17, RG-PL-4). The driver
 * returns the Postgres uuid columns as RFC 4122 strings.
 *
 * @phpstan-type InscriptionRow array{id: string, slot_id: string, origin: string, email: string|null, display_name: string, rank: int|string, starts_at: string, ends_at: string}
 */
final readonly class DoctrineAdminInscriptionViewFinder implements AdminInscriptionViewFinder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByEdition(EditionId $editionId, ?SlotId $slotId = null): array
    {
        $sql = 'SELECT i.id, i.slot_id, i.origin,
                       p.email, p.display_name,
                       s.rank, s.starts_at, s.ends_at
                FROM inscription i
                JOIN slot s ON s.id = i.slot_id
                JOIN priant p ON p.id = i.priant_id
                WHERE i.edition_id = ?';
        $parameters = [$editionId->value->toRfc4122()];

        if (null !== $slotId) {
            $sql .= ' AND i.slot_id = ?';
            $parameters[] = $slotId->value->toRfc4122();
        }

        $sql .= ' ORDER BY s.rank ASC, p.display_name ASC';

        /** @var list<InscriptionRow> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, $parameters);

        $views = [];
        foreach ($rows as $row) {
            $startsAt = new \DateTimeImmutable($row['starts_at']);
            $endsAt = new \DateTimeImmutable($row['ends_at']);
            $timeSlot = new TimeSlot($startsAt, $endsAt);

            $views[] = new AdminInscriptionView(
                inscriptionId: Uuid::fromString($row['id'])->toRfc4122(),
                displayName: $row['display_name'],
                email: $row['email'],
                origin: $row['origin'],
                slotId: Uuid::fromString($row['slot_id'])->toRfc4122(),
                slotRank: (int) $row['rank'],
                dayLabel: DayLabel::for($startsAt),
                slotLabel: SlotLabel::for($timeSlot),
                startsAtUtc: $startsAt->format(\DateTimeInterface::ATOM),
                endsAtUtc: $endsAt->format(\DateTimeInterface::ATOM),
            );
        }

        return $views;
    }
}
