<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Email;

/**
 * Registers a priant on one or more slots of a single edition (RG-IN-1:
 * immediate registration, no email validation). The priant is found by email
 * or created with the given display name (RG-IN-2); consent is recorded
 * when asked (RG-IN-5).
 *
 * The confirmation e-mail (texts, agenda file) belongs to the off-session
 * visitor — its only receipt. In session, the screen is the confirmation:
 * `confirmByEmail` keeps the worker silent then.
 *
 * @see specs-fonctionnelles.md §5.4, RG-IN-1 to RG-IN-5
 */
final readonly class RegisterInscriptions implements CommandInterface
{
    /**
     * @param list<SlotId> $slotIds
     */
    public function __construct(
        public array $slotIds,
        public DisplayName $displayName,
        public ?Email $email,
        public bool $consent,
        public bool $confirmByEmail = true,
    ) {
    }
}
