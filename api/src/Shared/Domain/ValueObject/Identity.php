<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

trait Identity
{
    public readonly AbstractUid $value;

    final public function __construct(AbstractUid|string|null $value = null)
    {
        $this->value = match (true) {
            $value instanceof AbstractUid => $value,
            \is_string($value) => Uuid::fromString($value),
            default => Uuid::v7(),
        };
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
