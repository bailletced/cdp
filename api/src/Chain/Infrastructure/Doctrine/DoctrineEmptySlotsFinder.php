<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\EmptySlotView;
use App\Chain\Application\Query\EmptySlotsFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;

/**
 * Doctrine implementation of EmptySlotsFinder (F13-T02): one SQL query. A
 * slot is empty when no inscription points to it (RG-PL-2); during the
 * edition, only the slots still to come count (§5.3), so the running one is
 * left out.
 */
final readonly class DoctrineEmptySlotsFinder implements EmptySlotsFinder
{
    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function findByEdition(EditionId $editionId): array
    {
        /** @var list<array{id: string, rank: int|string, starts_at: string, ends_at: string, text_title: string|null, text_source: string|null}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            "SELECT s.id, s.rank, s.starts_at, s.ends_at, t.title AS text_title, t.source AS text_source
             FROM slot s
             JOIN edition e ON e.id = s.edition_id
             LEFT JOIN text t ON t.id = s.text_id
             WHERE s.edition_id = :editionId
               AND e.lifecycle = 'scheduled'
               AND s.starts_at > :now
               AND NOT EXISTS (SELECT 1 FROM inscription i WHERE i.slot_id = s.id)
             ORDER BY s.rank ASC",
            [
                'editionId' => (string) $editionId,
                'now' => $this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:sP'),
            ],
        );

        $utc = new \DateTimeZone('UTC');

        return array_map(
            static function (array $row) use ($utc): EmptySlotView {
                $startsAt = new \DateTimeImmutable($row['starts_at'])->setTimezone($utc);
                $endsAt = new \DateTimeImmutable($row['ends_at'])->setTimezone($utc);

                return new EmptySlotView(
                    $row['id'],
                    (int) $row['rank'],
                    $startsAt,
                    $endsAt,
                    SlotLabel::for(new TimeSlot($startsAt, $endsAt)),
                    $row['text_title'],
                    $row['text_source'],
                );
            },
            $rows,
        );
    }
}
