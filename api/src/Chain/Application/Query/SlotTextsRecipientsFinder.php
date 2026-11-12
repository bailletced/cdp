<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\SlotTextsRecipient;
use App\Chain\Domain\ValueObject\EditionId;

/**
 * Read model of the e-mail recipients of an edition (F24): every distinct
 * priant of the edition who has an e-mail address.
 */
interface SlotTextsRecipientsFinder
{
    /**
     * @return list<SlotTextsRecipient> One entry per priant, whatever the
     *                                  number of their slots
     */
    public function findByEdition(EditionId $editionId): array;
}
