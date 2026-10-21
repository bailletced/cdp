<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Application\Command\CommandInterface;

final readonly class UnscheduleEdition implements CommandInterface
{
    public function __construct(
        public EditionId $editionId,
    ) {
    }
}
