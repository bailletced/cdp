<?php

declare(strict_types=1);

namespace App\Chain\Application\Port;

use App\Chain\Application\Dto\InscriptionConfirmation;

/**
 * Sends the emails of the chain to the priants (T30, specs-techniques §7.2).
 */
interface Notifier
{
    /**
     * One email per inscription batch (US-11.1), with the agenda file attached.
     */
    public function confirmInscriptions(InscriptionConfirmation $confirmation): void;
}
