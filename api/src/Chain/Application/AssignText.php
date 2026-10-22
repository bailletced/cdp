<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Application\Command\CommandInterface;

/**
 * Assigns (or withdraws, with null) a text to a single slot (US-07.1).
 */
final readonly class AssignText implements CommandInterface
{
    public function __construct(
        public SlotId $slotId,
        public ?TextId $textId,
    ) {
    }
}
