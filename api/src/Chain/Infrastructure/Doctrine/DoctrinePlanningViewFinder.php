<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\PlanningParticipantView;
use App\Chain\Application\Dto\PlanningSlotView;
use App\Chain\Application\Dto\PlanningView;
use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Domain\Time\PartOfDay;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;

/**
 * Doctrine implementation of PlanningViewFinder (F08-T03).
 *
 * Loads the 48 slots of a public (scheduled) edition in a single SQL query
 * with their attributed text title/source and their participants, computes
 * the temporal state of each slot (past / current / future) and counts the
 * registrations of each slot (RG-PL-1). The participants are masked by the
 * provider (RG-PL-4).
 */
final readonly class DoctrinePlanningViewFinder implements PlanningViewFinder
{
    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
    ) {
    }

    public function findByEdition(EditionId $editionId): ?PlanningView
    {
        // The edition must be scheduled (public): drafts are not visible.
        /** @var array{id: string}|false $editionRow */
        $editionRow = $this->connection->fetchAssociative(
            "SELECT id FROM edition WHERE id = :id AND lifecycle = 'scheduled'",
            ['id' => $editionId->value->toRfc4122()]
        );

        if (false === $editionRow) {
            return null;
        }

        $now = $this->clock->now();

        // UUID v7 inscription ids sort in registration order. The pointage
        // (RG-LV-2) comes with F19: no inscription has prayed yet.
        /** @var list<array{id: string, rank: int|string, starts_at: string, ends_at: string, text_title: string|null, text_source: string|null, registration_count: int|string, participants: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            "SELECT s.id, s.rank, s.starts_at, s.ends_at, t.title AS text_title, t.source AS text_source,
                    COUNT(i.id) AS registration_count,
                    COALESCE(
                        json_agg(json_build_object('inscriptionId', i.id, 'priantId', p.id, 'displayName', p.display_name) ORDER BY i.id)
                            FILTER (WHERE i.id IS NOT NULL),
                        '[]'
                    ) AS participants
             FROM slot s
             LEFT JOIN text t ON t.id = s.text_id
             LEFT JOIN inscription i ON i.slot_id = s.id
             LEFT JOIN priant p ON p.id = i.priant_id
             WHERE s.edition_id = :editionId
             GROUP BY s.id, t.title, t.source
             ORDER BY s.rank ASC",
            ['editionId' => $editionRow['id']]
        );

        $slotViews = [];
        foreach ($rows as $row) {
            $startsAt = new \DateTimeImmutable($row['starts_at']);
            $endsAt = new \DateTimeImmutable($row['ends_at']);
            $timeSlot = new TimeSlot($startsAt, $endsAt);

            $state = match (true) {
                $now < $startsAt => 'future',
                $now < $endsAt => 'current',
                default => 'past',
            };

            // RG-PL-1: the registration count of a slot is its number of inscriptions.
            $registrationCount = (int) $row['registration_count'];

            $slotViews[] = new PlanningSlotView(
                id: \Symfony\Component\Uid\Uuid::fromString($row['id'])->toRfc4122(),
                rank: (int) $row['rank'],
                startsAtUtc: $startsAt->format(\DateTimeInterface::ATOM),
                endsAtUtc: $endsAt->format(\DateTimeInterface::ATOM),
                label: SlotLabel::for($timeSlot),
                partOfDay: PartOfDay::forSlot($timeSlot)->value,
                state: $state,
                textTitle: $row['text_title'],
                textSource: $row['text_source'],
                registrationCount: $registrationCount,
                occupied: $registrationCount > 0,
                participants: $this->participants($row['participants']),
            );
        }

        return new PlanningView(
            editionId: $editionId->value->toRfc4122(),
            slots: $slotViews,
        );
    }

    /**
     * @return list<PlanningParticipantView>
     */
    private function participants(string $json): array
    {
        /** @var list<array{inscriptionId: string, priantId: string, displayName: string}> $participants */
        $participants = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        return array_map(
            static fn (array $participant): PlanningParticipantView => new PlanningParticipantView(
                $participant['inscriptionId'],
                $participant['priantId'],
                $participant['displayName'],
                prayed: false,
            ),
            $participants,
        );
    }
}
