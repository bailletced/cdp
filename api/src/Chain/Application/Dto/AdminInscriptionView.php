<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * One inscription as read by an organizer (US-16.2, F16-T03): who is
 * registered — display name and e-mail address — on which slot. A priant
 * registered by an organizer may have no e-mail at all (F16): email is null
 * then. The e-mail addresses are served under /api/admin only (T17,
 * RG-PL-4): the public planning never leaks them.
 */
final readonly class AdminInscriptionView
{
    public function __construct(
        public string $inscriptionId,
        public string $displayName,
        public ?string $email,
        public string $origin,
        public string $slotId,
        public int $slotRank,
        public string $slotLabel,
        public string $startsAtUtc,
        public string $endsAtUtc,
    ) {
    }
}
