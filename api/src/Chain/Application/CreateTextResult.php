<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\TextId;

/**
 * Result of CreateText: the identifier of the created text.
 */
final readonly class CreateTextResult
{
    public function __construct(
        public TextId $textId,
    ) {
    }
}
