<?php

declare(strict_types=1);

namespace App\Chain\Application\Port;

use App\Chain\Application\Dto\InscriptionDigestSlot;

/**
 * Builds the agenda file (.ics) of a priant's slots (T28, F11-T02).
 */
interface CalendarFile
{
    /**
     * One event per inscription, at its absolute (UTC) instants, with an
     * alarm 10 minutes before. Re-importing the file updates the events
     * instead of duplicating them (same UID, SEQUENCE = inscription version).
     *
     * @param list<InscriptionDigestSlot> $slots
     *
     * @return string The iCalendar content
     */
    public function forSlots(string $editionTitle, array $slots): string;
}
