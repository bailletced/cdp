<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a registration targets a slot that is already over
 * (specs-fonctionnelles.md §5.2: only the current slot and future slots can
 * be taken while the edition is running).
 */
#[ErrorResource(openapi: false)]
final class SlotAlreadyPastException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::CONFLICT;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'slot.already-past',
            errorDetail: 'Ce créneau est déjà passé.',
            errorCode: self::ERROR_CODE,
        );
    }
}
