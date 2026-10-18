<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ApiPlatform\Exception;

use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\ConflictException;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use PHPUnit\Framework\TestCase;

/**
 * Test pour F00-T16 : Exceptions personnalisées pour les codes HTTP.
 */
final class ExceptionTest extends TestCase
{
    public function testMissingModelExceptionHasCorrectStatusCode(): void
    {
        $exception = new MissingModelException('TestModel');

        $this->assertSame(404, $exception->getStatusCode());
        $this->assertStringContainsString('TestModel', $exception->getMessage());
    }

    public function testConflictExceptionHasCorrectStatusCode(): void
    {
        $exception = new ConflictException();

        $this->assertSame(409, $exception->getStatusCode());
    }

    public function testAccessDeniedExceptionHasCorrectStatusCode(): void
    {
        $exception = new AccessDeniedException();

        $this->assertSame(403, $exception->getStatusCode());
    }

    public function testConflictExceptionWithCustomMessage(): void
    {
        $message = 'Conflit personnalisé';
        $exception = new ConflictException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(409, $exception->getStatusCode());
    }

    public function testAccessDeniedExceptionWithCustomMessage(): void
    {
        $message = 'Accès refusé personnalisé';
        $exception = new AccessDeniedException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(403, $exception->getStatusCode());
    }
}
