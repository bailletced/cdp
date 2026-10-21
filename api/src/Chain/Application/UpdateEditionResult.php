<?php

declare(strict_types=1);

namespace App\Chain\Application;

final readonly class UpdateEditionResult
{
    public function __construct(
        public \DateTimeImmutable $resolvedStartsAtUtc,
        public string $resolvedStartsAtLabel,
    ) {
    }
}
