<?php

declare(strict_types=1);

namespace App\Tests\Integration\Fixture;

use App\Shared\Domain\ValueObject\Identifier;
use App\Shared\Domain\ValueObject\Identity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProbeAggregateId implements Identifier
{
    use Identity;
}
