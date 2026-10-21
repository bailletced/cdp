<?php

declare(strict_types=1);

namespace App\Shared\Domain\Time;

/**
 * A real 30-minute time slot in UTC, produced by ParisCalendar::slice().
 */
final readonly class TimeSlot
{
    public function __construct(
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
    ) {
    }
}
