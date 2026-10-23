<?php

declare(strict_types=1);

namespace App\Chain\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringConstraintsInterface;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Display name of a priant (RG-IN-2): one email = one priant = one name,
 * e.g. « Marie Dupont » or « Famille Martin ». Carried by the Priant, never
 * by the Inscription.
 */
#[ORM\Embeddable]
final readonly class DisplayName implements StringConstraintsInterface
{
    public const int MIN_LENGTH = 1;

    public const int MAX_LENGTH = 80;

    #[ORM\Column(name: 'display_name', length: self::MAX_LENGTH)]
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        $length = mb_strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(\sprintf('Le nom affiché doit comporter entre %d et %d caractères.', self::MIN_LENGTH, self::MAX_LENGTH));
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
