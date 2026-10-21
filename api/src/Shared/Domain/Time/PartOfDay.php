<?php

declare(strict_types=1);

namespace App\Shared\Domain\Time;

use DateTimeZone;

/**
 * Part of the day in Paris local time, used to group slots (specs §5.2).
 *
 * - Evening:   18h–0h  (soirée)
 * - Night:      0h–6h  (nuit)
 * - Morning:    6h–12h (matin)
 * - Afternoon: 12h–18h (après-midi)
 */
enum PartOfDay: string
{
    case Evening = 'evening';
    case Night = 'night';
    case Morning = 'morning';
    case Afternoon = 'afternoon';

    private const string PARIS_TIMEZONE = 'Europe/Paris';

    public static function forSlot(TimeSlot $slot): self
    {
        $parisTz = new DateTimeZone(self::PARIS_TIMEZONE);
        $localStart = $slot->startsAt->setTimezone($parisTz);
        $hour = (int) $localStart->format('G');

        return match (true) {
            $hour >= 18 => self::Evening,  // 18h–23h
            $hour < 6 => self::Night,      // 0h–5h
            $hour < 12 => self::Morning,   // 6h–11h
            default => self::Afternoon,    // 12h–17h
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Evening => 'soirée',
            self::Night => 'nuit',
            self::Morning => 'matin',
            self::Afternoon => 'après-midi',
        };
    }
}
