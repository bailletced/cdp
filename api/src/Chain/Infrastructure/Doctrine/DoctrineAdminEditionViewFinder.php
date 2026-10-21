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
 * @phpstan-type SlotRow array{rank: int|string, starts_at: string, ends_at: string, text_id: string|null}
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
            [$id->value->toBinary()]
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
        $editionId = Uuid::fromBinary($row['id']);
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

        // Load slots
        /** @var list<SlotRow> $slotRows */
        $slotRows = $this->connection->fetchAllAssociative(
            'SELECT rank, starts_at, ends_at, text_id
             FROM slot
             WHERE edition_id = ?
             ORDER BY rank ASC',
            [$row['id']]
        );

        $slotViews = [];
        foreach ($slotRows as $slotRow) {
            $slotStartsAt = new \DateTimeImmutable($slotRow['starts_at']);
            $slotEndsAt = new \DateTimeImmutable($slotRow['ends_at']);
            $timeSlot = new TimeSlot($slotStartsAt, $slotEndsAt);

            $slotViews[] = new AdminSlotView(
                rank: (int) $slotRow['rank'],
                startsAtUtc: $slotStartsAt->format(\DateTimeInterface::ATOM),
                endsAtUtc: $slotEndsAt->format(\DateTimeInterface::ATOM),
                label: SlotLabel::for($timeSlot),
                partOfDay: PartOfDay::forSlot($timeSlot)->value,
                textId: null !== $slotRow['text_id'] ? Uuid::fromBinary($slotRow['text_id'])->toRfc4122() : null,
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
        );
    }
}
