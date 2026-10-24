<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\MyInscriptionView;
use App\Chain\Application\Dto\MySpaceView;
use App\Chain\Application\Query\MySpaceViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\Time\DayLabel;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;

/**
 * Doctrine implementation of MySpaceViewFinder (F14-T01): one query for the
 * priant and the edition, one for the inscriptions with their slots and
 * texts. The temporal state and the actions follow the clock.
 */
final readonly class DoctrineMySpaceViewFinder implements MySpaceViewFinder
{
    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function find(PriantId $priantId, EditionId $editionId): ?MySpaceView
    {
        $parameters = ['priantId' => (string) $priantId, 'editionId' => (string) $editionId];

        /** @var array{display_name: string, edition_title: string}|false $header */
        $header = $this->connection->fetchAssociative(
            'SELECT p.display_name, e.title AS edition_title
             FROM priant p, edition e
             WHERE p.id = :priantId AND e.id = :editionId',
            $parameters,
        );

        if (false === $header) {
            return null;
        }

        /** @var list<array{inscription_id: string, slot_id: string, starts_at: string, ends_at: string, text_title: string|null, text_source: string|null}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT i.id AS inscription_id, s.id AS slot_id, s.starts_at, s.ends_at,
                    t.title AS text_title, t.source AS text_source
             FROM inscription i
             JOIN slot s ON s.id = i.slot_id
             LEFT JOIN text t ON t.id = s.text_id
             WHERE i.priant_id = :priantId AND i.edition_id = :editionId
             ORDER BY s.rank ASC',
            $parameters,
        );

        $utc = new \DateTimeZone('UTC');
        $now = $this->clock->now();

        return new MySpaceView(
            $header['display_name'],
            (string) $editionId,
            $header['edition_title'],
            array_map(
                static function (array $row) use ($utc, $now): MyInscriptionView {
                    $startsAt = new \DateTimeImmutable($row['starts_at'])->setTimezone($utc);
                    $endsAt = new \DateTimeImmutable($row['ends_at'])->setTimezone($utc);
                    $state = match (true) {
                        $now >= $endsAt => 'past',
                        $now >= $startsAt => 'current',
                        default => 'future',
                    };

                    return new MyInscriptionView(
                        $row['inscription_id'],
                        $row['slot_id'],
                        $startsAt,
                        $endsAt,
                        SlotLabel::for(new TimeSlot($startsAt, $endsAt)),
                        DayLabel::for($startsAt),
                        $row['text_title'],
                        $row['text_source'],
                        $state,
                        // RG-EP-1: free cancellation until the slot starts
                        'future' === $state ? [MyInscriptionView::ACTION_CANCEL] : [],
                    );
                },
                $rows,
            ),
        );
    }
}
