<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Email implements StringConstraintsInterface
{
    public const int MIN_LENGTH = 3;
    public const int MAX_LENGTH = 254;

    #[ORM\Column(name: 'email', length: self::MAX_LENGTH)]
    public string $value;

    public function __construct(string $value)
    {
        $value = \mb_strtolower(\trim($value));

        $length = \mb_strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf("L'adresse e-mail doit comporter entre %d et %d caractères.", self::MIN_LENGTH, self::MAX_LENGTH));
        }

        if (false === \filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'adresse e-mail n'est pas valide.");
        }

        $this->value = $value;
    }

    #[\Override]
    public static function getMinLength(): ?int
    {
        return self::MIN_LENGTH;
    }

    #[\Override]
    public static function getMaxLength(): ?int
    {
        return self::MAX_LENGTH;
    }
}
