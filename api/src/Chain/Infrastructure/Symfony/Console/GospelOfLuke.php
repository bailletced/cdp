<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

/**
 * The Gospel according to Luke in 48 readings, one per slot of an edition:
 * the Louis Segond translation (1910, public domain), from the USFM files of
 * eBible.org (fraLSG). The whole gospel, every verse once, in order.
 *
 * The readings are cut at the start of a pericope, so that they have a
 * comparable length (about 3,000 characters each); the section titles of
 * the edition head their passage. Bodies stay within what the editor
 * produces: the section titles in strong, the verse numbers in em.
 */
final class GospelOfLuke
{
    private const string READINGS = __DIR__.'/Resources/gospel-of-luke-segond-1910.json';

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
