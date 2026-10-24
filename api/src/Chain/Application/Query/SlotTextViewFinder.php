<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\SlotTextView;
use App\Chain\Domain\ValueObject\SlotId;

/**
 * Read model of the reading page of a slot (F12-T01). Who may read it is
 * decided by the VisibilityPolicy, not here.
 */
interface SlotTextViewFinder
{
    /**
     * The slot with its full text; null when the slot does not exist.
     */
    public function findBySlot(SlotId $slotId): ?SlotTextView;
}
