<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use App\Shared\Application\Command\CommandInterface;

final readonly class WriteProbeCommand implements CommandInterface
{
    public function __construct(
        public string $value,
        public bool $fail = false,
    ) {
    }
}
