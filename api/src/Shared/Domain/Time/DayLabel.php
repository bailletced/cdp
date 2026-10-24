<?php

declare(strict_types=1);

namespace App\Shared\Domain\Time;

/**
 * The Paris day of an instant, in French: « samedi 28 novembre ». An edition
 * spans two days, so a slot label alone (« 0h30–1h00 ») needs its day.
 */
final readonly class DayLabel
{
    public static function for(\DateTimeImmutable $instant): string
    {
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'Europe/Paris', \IntlDateFormatter::GREGORIAN, 'EEEE d MMMM');

        return (string) $formatter->format($instant);
    }
}
