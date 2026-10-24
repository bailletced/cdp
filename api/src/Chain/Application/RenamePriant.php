<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Application\Command\CommandInterface;

/**
 * Changes the display name of a priant (F14-T03, US-14.2): by the priant in
 * session (PATCH /api/me), or by an organizer (F16).
 */
final readonly class RenamePriant implements CommandInterface
{
    public function __construct(
        public PriantId $priantId,
        public DisplayName $displayName,
    ) {
    }
}
