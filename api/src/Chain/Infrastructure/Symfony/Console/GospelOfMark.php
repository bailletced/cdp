<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

/**
 * The Gospel according to Mark in 48 readings, one per slot of an edition:
 * the Louis Segond translation (1910, public domain), from the JSON export
 * of getbible.net (ls1910). The whole gospel, every verse once, in order —
 * the long ending (16, 9-20) included.
 *
 * The readings follow the reading table of the 2026 printed support
 * (« document pour vivre les 24 heures de la prière »): one passage per
 * half-hour slot, from Saturday 22:00 (Marc 1, 1-13) to Sunday 21:30
 * (Marc 16, 9-20); the section titles head their passage, and
 * each reading comes with a meditation hint (« piste d'oraison ») written
 * for the 2026 edition — the hints column was dropped since, but the
 * cutting stays. Bodies stay within what the editor produces: the
 * section titles in strong, the verse numbers in em.
 */
final class GospelOfMark
{
    private const string READINGS = __DIR__.'/Resources/gospel-of-mark-segond-1910.json';

    /**
     * @return list<array{title: string, source: string, body: string}>
     */
    public static function readings(): array
    {
        /** @var list<array{title: string, source: string, body: string}> $readings */
        $readings = json_decode((string) file_get_contents(self::READINGS), true, 512, \JSON_THROW_ON_ERROR);

        return $readings;
    }
}
