<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use RuntimeException;

/**
 * Base of the whole domain-exception hierarchy: one final child per business error is the DESIGN (RFC 7807
 * mapping + stable error codes), so the child count grows with the migrated surface by construction.
 *
 * API Platform turns any ProblemExceptionInterface into an RFC 7807 error response: the Infrastructure
 * layer no longer converts domain exceptions into HTTP exceptions.
 *
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
abstract class DomainException extends RuntimeException implements ProblemExceptionInterface
{
    public function __construct(
        private readonly string $errorTitle,
        private readonly string $errorDetail,
        private readonly DomainErrorCode $errorCode,
    ) {
        parent::__construct($errorDetail);
    }

    /**
     * RFC 7807 problem type: a STABLE relative URI identifying the error *category*, derived from the
     * {@see DomainErrorCode} (e.g. NOT_FOUND -> `/errors/not-found`, CONFLICT -> `/errors/conflict`).
     */
    public function getType(): string
    {
        return '/errors/'.strtolower(str_replace('_', '-', $this->errorCode->name));
    }

    public function getTitle(): ?string
    {
        return $this->errorTitle;
    }

    public function getDetail(): ?string
    {
        return $this->errorDetail;
    }

    public function getStatus(): int
    {
        return self::statusFor($this->errorCode);
    }

    /**
     * Single source of truth mapping a domain error code to its HTTP status.
     */
    public static function statusFor(DomainErrorCode $errorCode): int
    {
        return match ($errorCode) {
            DomainErrorCode::NOT_FOUND => 404,
            DomainErrorCode::DELETED => 410,
            DomainErrorCode::CONFLICT => 409,
            DomainErrorCode::PRECONDITION_FAILED => 412,
            DomainErrorCode::NOT_AUTHORIZED => 403,
            DomainErrorCode::NOT_AUTHENTICATED => 401,
            DomainErrorCode::INVALID_INPUT, DomainErrorCode::BAD_REQUEST => 400,
        };
    }

    public function getInstance(): ?string
    {
        return null;
    }
}
