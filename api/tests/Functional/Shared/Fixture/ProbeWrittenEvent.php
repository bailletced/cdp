<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use App\Shared\Domain\Event\EventInterface;

final readonly class ProbeWrittenEvent implements EventInterface
{
    public function __construct(public string $value)
    {
    }
}
