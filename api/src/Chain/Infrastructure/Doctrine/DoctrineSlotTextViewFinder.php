<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\SlotTextContent;
use App\Chain\Application\Dto\SlotTextView;
use App\Chain\Application\Query\SlotTextViewFinder;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\Time\DayLabel;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;

/**
 * Doctrine implementation of SlotTextViewFinder (F12-T01): one SQL query
 * joins the slot to its edition and its text. The labels are Paris
 * wall-clock ranges (ParisCalendar, RG-ED-5).
 */
final readonly class DoctrineSlotTextViewFinder implements SlotTextViewFinder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function findBySlot(SlotId $slotId): ?SlotTextView
    {
        /** @var array{slot_id: string, edition_id: string, edition_title: string, starts_at: string, ends_at: string, text_title: string|null, text_source: string|null, text_body: string|null, text_hints: string|null}|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT s.id AS slot_id, e.id AS edition_id, e.title AS edition_title, s.starts_at, s.ends_at,
                    t.title AS text_title, t.source AS text_source, t.body AS text_body, t.hints AS text_hints
             FROM slot s
             JOIN edition e ON e.id = s.edition_id
             LEFT JOIN text t ON t.id = s.text_id
             WHERE s.id = :slotId',
            ['slotId' => (string) $slotId],
        );

        if (false === $row) {
            return null;
        }

        $utc = new \DateTimeZone('UTC');
        $startsAt = new \DateTimeImmutable($row['starts_at'])->setTimezone($utc);
        $endsAt = new \DateTimeImmutable($row['ends_at'])->setTimezone($utc);

        return new SlotTextView(
            $row['slot_id'],
            $row['edition_id'],
            $row['edition_title'],
            $startsAt,
            $endsAt,
            SlotLabel::for(new TimeSlot($startsAt, $endsAt)),
            DayLabel::for($startsAt),
            null === $row['text_title'] || null === $row['text_source'] || null === $row['text_body']
                ? null
                : new SlotTextContent($row['text_title'], $row['text_source'], $row['text_body'], $row['text_hints']),
        );
    }
}
