<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Application\Command\CommandInterface;

/**
 * Deletes a text of the library (US-06.3, RG-TX-3).
 */
final readonly class DeleteText implements CommandInterface
{
    public function __construct(
        public TextId $textId,
    ) {
    }
}
