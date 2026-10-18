<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Interface for value objects with string length constraints.
 * Lets AssertValueObject read the min/max bounds without reflection.
 */
interface StringConstraintsInterface
{
    /**
     * Returns the minimum allowed length.
     *
     * @return int<1, max>
     */
    public static function getMinLength(): int;

    /**
     * Returns the maximum allowed length.
     *
     * @return int<1, max>
     */
    public static function getMaxLength(): int;
}
