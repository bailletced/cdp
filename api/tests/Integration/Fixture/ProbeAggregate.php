<?php

declare(strict_types=1);

namespace App\Tests\Integration\Fixture;

use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'probe_aggregate')]
final class ProbeAggregate
{
    public function __construct(
        public readonly ProbeAggregateId $id,
        public readonly Email $email,
    ) {
    }
}
