<?php

declare(strict_types=1);

namespace App\Shared\Domain\Time;

use DateTimeZone;

/**
 * Produces a human-readable label for a TimeSlot in Paris local time.
 *
 * Format: "3h00–3h30". When the local time is ambiguous (DST fall-back),
 * the label is disambiguated: "2h00 (heure d’été)–2h30 (heure d’été)"
 * or "2h00 (heure d’hiver)–2h30 (heure d’hiver)" (RG-ED-5).
 */
final readonly class SlotLabel
{
    private const string PARIS_TIMEZONE = 'Europe/Paris';

    private const string UTC_TIMEZONE = 'UTC';

    public static function for(TimeSlot $slot): string
    {
        $parisTz = new DateTimeZone(self::PARIS_TIMEZONE);

        $localStart = $slot->startsAt->setTimezone($parisTz);
        $localEnd = $slot->endsAt->setTimezone($parisTz);

        $startLabel = self::formatLocalTime($localStart, $slot->startsAt);
        $endLabel = self::formatLocalTime($localEnd, $slot->endsAt);

        return \sprintf('%s–%s', $startLabel, $endLabel);
    }

    /**
     * Format a local time as "HhMM", with DST disambiguation when ambiguous.
     */
    private static function formatLocalTime(\DateTimeImmutable $local, \DateTimeImmutable $originalUtc): string
    {
        $base = \sprintf('%dh%s', (int) $local->format('G'), $local->format('i'));

        if (!self::isAmbiguous($local, $originalUtc)) {
            return $base;
        }

        // Ambiguous: determine whether this occurrence is summer or winter time.
        $offset = (int) $local->format('Z');
        $isSummerTime = $offset >= 7200; // UTC+2 = CEST (summer), UTC+1 = CET (winter)

        $suffix = $isSummerTime ? 'heure d’été' : 'heure d’hiver';

        return \sprintf('%s (%s)', $base, $suffix);
    }

    /**
     * Detect whether a Paris local time is ambiguous (occurs twice due to DST fall-back).
     *
     * Strategy: take the wall-clock time, compute what UTC timestamp it would have
     * with the *other* DST offset (Paris is either UTC+1 or UTC+2), and check whether
     * converting that candidate UTC instant back to Paris yields the same wall-clock time.
     */
    private static function isAmbiguous(\DateTimeImmutable $local, \DateTimeImmutable $originalUtc): bool
    {
        $parisTz = new DateTimeZone(self::PARIS_TIMEZONE);
        $utcTz = new DateTimeZone(self::UTC_TIMEZONE);

        $wallClock = $local->format('Y-m-d H:i:s');

        // Interpret the wall clock as UTC to get a raw timestamp
        $asUtc = new \DateTimeImmutable($wallClock, $utcTz);
        $rawTimestamp = $asUtc->getTimestamp();

        // The original offset at this instant
        $originalOffset = (int) $originalUtc->setTimezone($parisTz)->format('Z');

        // The alternative offset (Paris alternates between 3600 and 7200)
        $altOffset = 3600 === $originalOffset ? 7200 : 3600;

        // Candidate UTC timestamp if the wall clock were at the alternative offset
        $candidateUtc = new \DateTimeImmutable('@'.($rawTimestamp - $altOffset))->setTimezone($utcTz);

        // Convert back to Paris: does it produce the same wall clock?
        $candidateLocal = $candidateUtc->setTimezone($parisTz);

        return $candidateLocal->format('Y-m-d H:i:s') === $wallClock;
    }
}
