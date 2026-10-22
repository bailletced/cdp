<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use App\Shared\Domain\Event\DomainEvent;

final readonly class ProbeWrittenEvent implements DomainEvent
{
    public function __construct(public string $value)
    {
    }
}
