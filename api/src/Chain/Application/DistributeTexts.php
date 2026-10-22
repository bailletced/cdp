<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Application\Command\CommandInterface;

/**
 * Distributes an ordered list of texts across the slots of an edition,
 * starting at the given rank (US-07.2). Truncated at slot 48.
 */
final readonly class DistributeTexts implements CommandInterface
{
    /**
     * @param list<TextId> $textIds
     */
    public function __construct(
        public EditionId $editionId,
        public array $textIds,
        public int $fromRank,
    ) {
    }
}
