<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a registration targets an edition whose inscriptions are not
 * open: the edition is still a draft, not open yet, or already finished
 * (specs-fonctionnelles.md §5.2: inscriptions are possible while the edition
 * is open or running).
 */
#[ErrorResource(openapi: false)]
final class EditionClosedForInscriptionsException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::CONFLICT;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'edition.closed-for-inscriptions',
            errorDetail: 'Les inscriptions ne sont pas ouvertes pour cette édition.',
            errorCode: self::ERROR_CODE,
        );
    }
}
