<?php

declare(strict_types=1);

namespace App\Chain\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringConstraintsInterface;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final readonly class TextSource implements StringConstraintsInterface
{
    public const int MIN_LENGTH = 1;

    public const int MAX_LENGTH = 255;

    #[ORM\Column(name: 'source', length: self::MAX_LENGTH)]
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value) {
            throw new InvalidArgumentException('La source est obligatoire.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(\sprintf('La source ne doit pas dépasser %d caractères.', self::MAX_LENGTH));
        }

        $this->value = $value;
    }

    #[\Override]
    public static function getMinLength(): int
    {
        return self::MIN_LENGTH;
    }

    #[\Override]
    public static function getMaxLength(): int
    {
        return self::MAX_LENGTH;
    }
}
