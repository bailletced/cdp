<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MissingModelException extends NotFoundHttpException
{
    public function __construct(string $modelClass, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Le modèle "%s" est introuvable.', $modelClass), $previous);
    }
}
