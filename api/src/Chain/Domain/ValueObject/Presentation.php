<?php

declare(strict_types=1);

namespace App\Chain\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringConstraintsInterface;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final readonly class Presentation implements StringConstraintsInterface
{
    public const int MIN_LENGTH = 0;

    public const int MAX_LENGTH = 2000;

    #[ORM\Column(name: 'presentation', type: 'text', length: self::MAX_LENGTH, nullable: true)]
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        $length = mb_strlen($value);
        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(\sprintf('La présentation ne doit pas dépasser %d caractères.', self::MAX_LENGTH));
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
