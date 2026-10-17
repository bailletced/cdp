<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList as ValueObjectSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    // Configuration Symfony
    $rectorConfig->symfony();
    $rectorConfig->sets([
        ValueObjectSetList::VALUE_OBJECT_SET,
    ]);

    // Exclure les fichiers générés
    $rectorConfig->skip([
        __DIR__.'/var',
        __DIR__.'/vendor',
        __DIR__.'/migrations',
        __DIR__.'/public',
    ]);

    // PHP 8.4
    $rectorConfig->phpVersion('8.4');
};
