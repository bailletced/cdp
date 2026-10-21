<?php

declare(strict_types=1);

namespace App\Chain\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Identifier;
use App\Shared\Domain\ValueObject\Identity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class EditionId implements Identifier
{
    use Identity;

    public function __toString(): string
    {
        return $this->value->toRfc4122();
    }
}
