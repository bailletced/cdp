<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait Identity
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'uuid')]
    public readonly Uuid $value;

    final public function __construct(Uuid|string|null $value = null)
    {
        $this->value = match (true) {
            $value instanceof Uuid => $value,
            \is_string($value) => Uuid::fromString($value),
            default => Uuid::v7(),
        };
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
