<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Domain\Time\PartOfDay;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Doctrine implementation of AdminEditionViewFinder.
 * Reads editions and slots from the database, computes status and labels.
 *
 * @phpstan-type EditionRow array{id: string, title: string, presentation: string|null, starts_at: string, opens_at: string, lifecycle: string, scheduled_at: string|null, version: int|string}
 * @phpstan-type SlotRow array{id: string, rank: int|string, starts_at: string, ends_at: string, text_id: string|null, text_title: string|null, text_source: string|null, text_already_used: bool}
 */
final readonly class DoctrineAdminEditionViewFinder implements AdminEditionViewFinder
{
    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
    ) {
    }

    public function findAll(): array
    {
        /** @var list<EditionRow> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, title, presentation, starts_at, opens_at, lifecycle, scheduled_at, version
             FROM edition
             ORDER BY starts_at DESC'
        );

        $views = [];
        foreach ($rows as $row) {
            $views[] = $this->hydrateView($row);
        }

        return $views;
    }

    public function findOne(EditionId $id): ?AdminEditionView
    {
        /** @var EditionRow|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT id, title, presentation, starts_at, opens_at, lifecycle, scheduled_at, version
             FROM edition
             WHERE id = ?',
            [$id->value->toRfc4122()]
        );

        if (false === $row) {
            return null;
        }

        return $this->hydrateView($row);
    }

    /**
     * @param EditionRow $row
     */
    private function hydrateView(array $row): AdminEditionView
    {
        // The driver returns Postgres uuid columns as RFC 4122 strings
        $editionId = Uuid::fromString($row['id']);
        $startsAt = new \DateTimeImmutable($row['starts_at']);
        $opensAt = new \DateTimeImmutable($row['opens_at']);

        // Compute status from lifecycle and dates
        $lifecycle = $row['lifecycle'];
        $now = $this->clock->now();

        if ('draft' === $lifecycle) {
            $status = 'draft';
        } else {
            $endsAt = $startsAt->modify('+24 hours');
            $status = match (true) {
                $now < $opensAt => 'scheduled',
                $now < $startsAt => 'open',
                $now < $endsAt => 'running',
                default => 'finished',
            };
        }

        // Load slots with their text (F07-T04): a window over text_id flags
        // a text carried by several slots of the edition
        /** @var list<SlotRow> $slotRows */
        $slotRows = $this->connection->fetchAllAssociative(
            'SELECT s.id, s.rank, s.starts_at, s.ends_at, s.text_id,
                    t.title AS text_title, t.source AS text_source,
                    (s.text_id IS NOT NULL AND COUNT(s.text_id) OVER (PARTITION BY s.text_id) > 1) AS text_already_used
             FROM slot s
             LEFT JOIN text t ON t.id = s.text_id
             WHERE s.edition_id = ?
             ORDER BY s.rank ASC',
            [$row['id']]
        );

        /** @var int|string $slotsWithoutText */
        $slotsWithoutText = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM slot WHERE edition_id = ? AND text_id IS NULL',
            [$row['id']]
        );

        /** @var list<string> $duplicatedTextIds */
        $duplicatedTextIds = $this->connection->fetchFirstColumn(
            'SELECT text_id FROM slot
             WHERE edition_id = ? AND text_id IS NOT NULL
             GROUP BY text_id
             HAVING COUNT(*) > 1
             ORDER BY MIN(rank)',
            [$row['id']]
        );

        $slotViews = [];
        foreach ($slotRows as $slotRow) {
            $slotStartsAt = new \DateTimeImmutable($slotRow['starts_at']);
            $slotEndsAt = new \DateTimeImmutable($slotRow['ends_at']);
            $timeSlot = new TimeSlot($slotStartsAt, $slotEndsAt);

            $slotViews[] = new AdminSlotView(
                id: Uuid::fromString($slotRow['id'])->toRfc4122(),
                rank: (int) $slotRow['rank'],
                startsAtUtc: $slotStartsAt->format(\DateTimeInterface::ATOM),
                endsAtUtc: $slotEndsAt->format(\DateTimeInterface::ATOM),
                label: SlotLabel::for($timeSlot),
                partOfDay: PartOfDay::forSlot($timeSlot)->value,
                textId: null !== $slotRow['text_id'] ? Uuid::fromString($slotRow['text_id'])->toRfc4122() : null,
                textTitle: $slotRow['text_title'],
                textSource: $slotRow['text_source'],
                textAlreadyUsed: (bool) $slotRow['text_already_used'],
            );
        }

        return new AdminEditionView(
            id: $editionId->toRfc4122(),
            title: $row['title'],
            presentation: $row['presentation'] ?? '',
            startsAtUtc: $startsAt->format(\DateTimeInterface::ATOM),
            opensAtUtc: $opensAt->format(\DateTimeInterface::ATOM),
            lifecycle: $lifecycle,
            status: $status,
            scheduledAtUtc: null !== $row['scheduled_at'] ? new \DateTimeImmutable($row['scheduled_at'])->format(\DateTimeInterface::ATOM) : null,
            version: (int) $row['version'],
            slots: $slotViews,
            slotsWithoutText: (int) $slotsWithoutText,
            duplicatedTextIds: array_map(static fn (string $id): string => Uuid::fromString($id)->toRfc4122(), $duplicatedTextIds),
        );
    }
}
