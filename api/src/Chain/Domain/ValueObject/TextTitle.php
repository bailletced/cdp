<?php

declare(strict_types=1);

namespace App\Chain\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringConstraintsInterface;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final readonly class TextTitle implements StringConstraintsInterface
{
    public const int MIN_LENGTH = 3;

    public const int MAX_LENGTH = 120;

    #[ORM\Column(name: 'title', length: self::MAX_LENGTH)]
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        $length = mb_strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(\sprintf('Le titre doit comporter entre %d et %d caractères.', self::MIN_LENGTH, self::MAX_LENGTH));
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
