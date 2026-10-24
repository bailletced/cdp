<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\InscriptionDigest;
use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;

/**
 * Doctrine implementation of InscriptionDigestFinder (F11-T03): one SQL
 * query joins the priant's inscriptions to their slots, texts and edition.
 * The labels are Paris wall-clock ranges (ParisCalendar, RG-ED-5).
 */
final readonly class DoctrineInscriptionDigestFinder implements InscriptionDigestFinder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function findByPriantAndEdition(PriantId $priantId, EditionId $editionId): ?InscriptionDigest
    {
        /** @var list<array{edition_title: string, edition_starts_at: string, inscription_id: string, version: int|string, starts_at: string, ends_at: string, text_title: string|null, text_source: string|null, text_body: string|null, text_hints: string|null}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT e.title AS edition_title, e.starts_at AS edition_starts_at,
                    i.id AS inscription_id, i.version, s.starts_at, s.ends_at,
                    t.title AS text_title, t.source AS text_source, t.body AS text_body, t.hints AS text_hints
             FROM inscription i
             JOIN slot s ON s.id = i.slot_id
             JOIN edition e ON e.id = i.edition_id
             LEFT JOIN text t ON t.id = s.text_id
             WHERE i.priant_id = :priantId AND i.edition_id = :editionId
             ORDER BY s.rank ASC',
            ['priantId' => (string) $priantId, 'editionId' => (string) $editionId],
        );

        if ([] === $rows) {
            return null;
        }

        $utc = new \DateTimeZone('UTC');
        $slots = array_map(
            static function (array $row) use ($utc): InscriptionDigestSlot {
                $startsAt = new \DateTimeImmutable($row['starts_at'])->setTimezone($utc);
                $endsAt = new \DateTimeImmutable($row['ends_at'])->setTimezone($utc);

                return new InscriptionDigestSlot(
                    $row['inscription_id'],
                    (int) $row['version'],
                    $startsAt,
                    $endsAt,
                    SlotLabel::for(new TimeSlot($startsAt, $endsAt)),
                    $row['text_title'],
                    $row['text_source'],
                    $row['text_body'],
                    $row['text_hints'],
                );
            },
            $rows,
        );

        return new InscriptionDigest(
            $rows[0]['edition_title'],
            new \DateTimeImmutable($rows[0]['edition_starts_at'])->setTimezone($utc)->modify('+24 hours'),
            $slots,
        );
    }
}
