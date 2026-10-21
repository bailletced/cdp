<?php

declare(strict_types=1);

namespace App\Shared\Domain\Time;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Single source of truth for Paris calendar computations (T9).
 *
 * All instants are stored in UTC. This service:
 * - slices a 24-hour edition into 48 real 30-minute slots (RG-ED-2) ;
 * - produces slot labels in Paris local time ;
 * - resolves a Paris local start time to UTC, with automatic DST resolution (RG-ED-7).
 */
final readonly class ParisCalendar
{
    public const int SLOT_COUNT = 48;

    public const int SLOT_DURATION_MINUTES = 30;

    private const string PARIS_TIMEZONE = 'Europe/Paris';

    private const string UTC_TIMEZONE = 'UTC';

    /**
     * Slice a 24-hour edition into 48 contiguous 30-minute slots starting at $startsAt (UTC).
     *
     * The start must fall on a full hour or half-hour (RG-ED-2).
     *
     * @return list<TimeSlot> 48 slots, contiguous, each exactly 30 minutes
     */
    public function slice(DateTimeImmutable $startsAt): array
    {
        $this->assertValidStart($startsAt);

        $slots = [];
        $current = $startsAt;

        for ($i = 0; $i < self::SLOT_COUNT; ++$i) {
            $endsAt = $current->modify(\sprintf('+%d minutes', self::SLOT_DURATION_MINUTES));
            $slots[] = new TimeSlot($current, $endsAt);
            $current = $endsAt;
        }

        return $slots;
    }

    /**
     * Resolve a Paris local date-time to a UTC instant.
     *
     * Automatic DST resolution (RG-ED-7):
     * - Ambiguous time (fall-back): takes the first occurrence (earliest UTC instant, summer time).
     * - Non-existent time (spring-forward): shifts forward by one hour.
     *
     * The local time must fall on :00 or :30 (RG-ED-2).
     */
    public function fromParisLocal(DateTimeImmutable $parisLocal): DateTimeImmutable
    {
        $parisTz = new DateTimeZone(self::PARIS_TIMEZONE);
        $utcTz = new DateTimeZone(self::UTC_TIMEZONE);

        // Ensure the input is in Paris timezone
        $local = $parisLocal->setTimezone($parisTz);

        // Validate :00 or :30
        $minutes = (int) $local->format('i');
        if (0 !== $minutes && 30 !== $minutes) {
            throw new InvalidArgumentException(\sprintf('The start time must fall on a full hour or half-hour (:00 or :30), got :%02d.', $minutes));
        }

        // Convert to UTC — PHP resolves DST automatically, but for ambiguous times
        // it picks the second occurrence (winter time). We need the first (summer time).
        $utc = $local->setTimezone($utcTz);

        // Detect ambiguity and adjust to the first occurrence if needed.
        if ($this->isAmbiguousLocal($local)) {
            // Compute the alternative UTC instant (the other DST offset).
            $wallClock = $local->format('Y-m-d H:i:s');
            $asUtc = new DateTimeImmutable($wallClock, $utcTz);
            $rawTimestamp = $asUtc->getTimestamp();

            $currentOffset = (int) $local->format('Z');
            $altOffset = 3600 === $currentOffset ? 7200 : 3600;

            $altUtc = new DateTimeImmutable('@'.($rawTimestamp - $altOffset))->setTimezone($utcTz);

            // Take the earliest UTC instant (first occurrence)
            if ($altUtc->getTimestamp() < $utc->getTimestamp()) {
                return $altUtc;
            }
        }

        return $utc;
    }

    /**
     * Returns the instant of "the eve at 18:00 Paris time" for a given edition start.
     * This is the deadline for the eve reminder (F24).
     */
    public function eveAt18h(DateTimeImmutable $startsAtUtc): DateTimeImmutable
    {
        $parisTz = new DateTimeZone(self::PARIS_TIMEZONE);
        $utcTz = new DateTimeZone(self::UTC_TIMEZONE);

        // Convert start to Paris local, go back one day, set to 18:00
        $parisStart = $startsAtUtc->setTimezone($parisTz);
        $eve = $parisStart->modify('-1 day')->setTime(18, 0, 0);

        // Convert back to UTC
        return $eve->setTimezone($utcTz);
    }

    /**
     * @throws InvalidArgumentException if the start does not fall on :00 or :30
     */
    private function assertValidStart(DateTimeImmutable $startsAt): void
    {
        $minutes = (int) $startsAt->format('i');
        if (0 !== $minutes && 30 !== $minutes) {
            throw new InvalidArgumentException(\sprintf('The edition start must fall on a full hour or half-hour (:00 or :30), got :%02d.', $minutes));
        }
    }

    /**
     * Detect whether a Paris local time is ambiguous (occurs twice due to DST fall-back).
     */
    private function isAmbiguousLocal(DateTimeImmutable $local): bool
    {
        $parisTz = new DateTimeZone(self::PARIS_TIMEZONE);
        $utcTz = new DateTimeZone(self::UTC_TIMEZONE);

        $wallClock = $local->format('Y-m-d H:i:s');

        // Interpret the wall clock as UTC to get a raw timestamp
        $asUtc = new DateTimeImmutable($wallClock, $utcTz);
        $rawTimestamp = $asUtc->getTimestamp();

        // The current offset at this instant
        $currentOffset = (int) $local->format('Z');

        // The alternative offset (Paris alternates between 3600 and 7200)
        $altOffset = 3600 === $currentOffset ? 7200 : 3600;

        // Candidate UTC timestamp if the wall clock were at the alternative offset
        $candidateUtc = new DateTimeImmutable('@'.($rawTimestamp - $altOffset))->setTimezone($utcTz);

        // Convert back to Paris: does it produce the same wall clock?
        $candidateLocal = $candidateUtc->setTimezone($parisTz);

        return $candidateLocal->format('Y-m-d H:i:s') === $wallClock;
    }
}
