<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\CommandInterface;

/**
 * Creates a new edition in draft lifecycle.
 * The start and open dates are given in Paris local time.
 */
final readonly class CreateEdition implements CommandInterface
{
    public function __construct(
        public EditionTitle $title,
        public Presentation $presentation,
        public \DateTimeImmutable $startsAtParisLocal,
        public \DateTimeImmutable $opensAtParisLocal,
    ) {
    }
}
