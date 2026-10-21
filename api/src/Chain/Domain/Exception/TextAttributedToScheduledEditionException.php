<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when deleting a text attributed to a scheduled edition (RG-TX-3).
 */
#[ErrorResource(openapi: false)]
final class TextAttributedToScheduledEditionException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::CONFLICT;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'text.attributed-to-scheduled-edition',
            errorDetail: 'Ce texte est attribué à une édition programmée : il ne peut pas être supprimé.',
            errorCode: self::ERROR_CODE,
        );
    }
}
