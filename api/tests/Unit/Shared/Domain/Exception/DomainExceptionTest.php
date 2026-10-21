<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Exception;

use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DomainExceptionTest extends TestCase
{
    #[DataProvider('errorCodeToStatusProvider')]
    public function testMapsErrorCodeToHttpStatus(DomainErrorCode $code, int $expectedStatus): void
    {
        self::assertSame($expectedStatus, $this->exception($code)->getStatus());
    }

    /**
     * @return iterable<string, array{DomainErrorCode, int}>
     */
    public static function errorCodeToStatusProvider(): iterable
    {
        yield 'not found' => [DomainErrorCode::NOT_FOUND, 404];
        yield 'deleted' => [DomainErrorCode::DELETED, 410];
        yield 'conflict' => [DomainErrorCode::CONFLICT, 409];
        yield 'precondition failed' => [DomainErrorCode::PRECONDITION_FAILED, 412];
        yield 'not authorized' => [DomainErrorCode::NOT_AUTHORIZED, 403];
        yield 'not authenticated' => [DomainErrorCode::NOT_AUTHENTICATED, 401];
        yield 'invalid input' => [DomainErrorCode::INVALID_INPUT, 400];
        yield 'bad request' => [DomainErrorCode::BAD_REQUEST, 400];
    }

    public function testExposesTheProblemDetails(): void
    {
        $exception = $this->exception(DomainErrorCode::NOT_FOUND);

        // The type is a stable category URI derived from the error code, NOT from the (machine-code) title.
        self::assertSame('/errors/not-found', $exception->getType());
        self::assertSame('some.title', $exception->getTitle());
        self::assertSame('Some detail', $exception->getDetail());
        self::assertSame('Some detail', $exception->getMessage());
        self::assertNull($exception->getInstance());
    }

    private function exception(DomainErrorCode $code): DomainException
    {
        return new class('some.title', 'Some detail', $code) extends DomainException {
        };
    }
}
