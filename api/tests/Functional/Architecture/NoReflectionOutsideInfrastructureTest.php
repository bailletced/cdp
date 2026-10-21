<?php

declare(strict_types=1);

namespace App\Tests\Functional\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Architecture test: reflection is forbidden outside infrastructure adapters.
 *
 * Domain and application code must never use ReflectionClass,
 * ReflectionProperty, ReflectionMethod, ... Reading or modifying an object's
 * state through reflection bypasses the aggregate's API and silently skips
 * its invariants. Reflection is only allowed under src/Shared/Infrastructure,
 * where adapters inspect metadata to plug into a framework (object mappers,
 * Doctrine mapping drivers, validators).
 */
final class NoReflectionOutsideInfrastructureTest extends TestCase
{
    private const string SRC_DIR = __DIR__.'/../../../src';

    private const string ALLOWED_DIR = '/Shared/Infrastructure/';

    private const string REFLECTION_PATTERN = '/\\\\?Reflection(Class|Property|Method|Function|Attribute)\b/';

    public function testNoReflectionOutsideInfrastructureAdapters(): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $path) {
            if (str_contains($path, self::ALLOWED_DIR)) {
                continue;
            }

            $code = file_get_contents($path);

            if (false !== $code && preg_match(self::REFLECTION_PATTERN, $code)) {
                $violations[] = str_replace(self::SRC_DIR.'/', '', $path);
            }
        }

        self::assertSame(
            [],
            $violations,
            "Reflection is forbidden outside src/Shared/Infrastructure (infrastructure adapters):\n - ".implode("\n - ", $violations),
        );
    }

    /**
     * @return iterable<string>
     */
    private function sourceFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::SRC_DIR, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && str_ends_with($file->getFilename(), '.php')) {
                yield $file->getPathname();
            }
        }
    }
}
