<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'migrations', 'public', 'node_modules'])
    ->append([
        __DIR__.'/rector.php',
        __DIR__.'/phpunit.xml',
    ])
;

return (new Config())
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP84Migration' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => false,
        'single_blank_line_before_namespace' => false,
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
            ],
        ],
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
;
