<?php

declare(strict_types=1);

namespace App\Access\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Identifier;
use App\Shared\Domain\ValueObject\Identity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @template-extends Identity<OrganizerId>
 */
#[ORM\Embeddable]
final readonly class OrganizerId implements Identifier
{
    use Identity;

    public function __toString(): string
    {
        return $this->value->toRfc4122();
    }
}
