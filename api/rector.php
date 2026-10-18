<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList as ValueObjectSetList;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        ValueObjectSetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->skip([
        __DIR__.'/var',
        __DIR__.'/vendor',
        __DIR__.'/migrations',
        __DIR__.'/public',
        // Test properties stay typed with the repository interfaces
        // (the project uses fakes, not PHPUnit MockObject).
        TypedPropertyFromCreateMockAssignRector::class,
    ]);

    // PHP 8.4
    $rectorConfig->phpVersion(80400);
};
