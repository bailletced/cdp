<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\InscriptionId;
use App\Shared\Application\Command\CommandInterface;

/**
 * Cancels an inscription (F14-T05, US-14.3): by its priant in session, until
 * the slot starts (RG-EP-1). Who may act is decided by the voter
 * (INSCRIPTION_ACT), not here.
 */
final readonly class CancelInscription implements CommandInterface
{
    public function __construct(
        public InscriptionId $inscriptionId,
    ) {
    }
}
