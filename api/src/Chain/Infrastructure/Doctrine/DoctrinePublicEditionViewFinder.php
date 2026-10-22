<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\PublicEditionView;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;

/**
 * Doctrine implementation of PublicEditionViewFinder (F08-T01).
 *
 * RG-ED-1: the current public edition is the most recent scheduled one
 * whose opening date has been reached:
 *   lifecycle = 'scheduled' AND opens_at <= now ORDER BY starts_at DESC LIMIT 1
 */
final readonly class DoctrinePublicEditionViewFinder implements PublicEditionViewFinder
{
    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
    ) {
    }

    public function current(): ?PublicEditionView
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        /** @var array{id: string, title: string, presentation: string|null, starts_at: string, opens_at: string, lifecycle: string}|false $row */
        $row = $this->connection->fetchAssociative(
            "SELECT id, title, presentation, starts_at, opens_at, lifecycle
             FROM edition
             WHERE lifecycle = 'scheduled' AND opens_at <= :now
             ORDER BY starts_at DESC
             LIMIT 1",
            ['now' => $now]
        );

        if (false === $row) {
            return null;
        }

        $startsAt = new \DateTimeImmutable($row['starts_at']);
        $opensAt = new \DateTimeImmutable($row['opens_at']);
        $endsAt = $startsAt->modify('+24 hours');
        $nowDate = $this->clock->now();

        $status = match (true) {
            $nowDate < $opensAt => 'scheduled',
            $nowDate < $startsAt => 'open',
            $nowDate < $endsAt => 'running',
            default => 'finished',
        };

        return new PublicEditionView(
            id: \Symfony\Component\Uid\Uuid::fromString($row['id'])->toRfc4122(),
            title: $row['title'],
            presentation: $row['presentation'] ?? '',
            startsAtUtc: $startsAt->format(\DateTimeInterface::ATOM),
            opensAtUtc: $opensAt->format(\DateTimeInterface::ATOM),
            startsAtLabel: SlotLabel::for(new TimeSlot($startsAt, $startsAt->modify('+30 minutes'))),
            opensAtLabel: SlotLabel::for(new TimeSlot($opensAt, $opensAt->modify('+30 minutes'))),
            status: $status,
        );
    }
}
