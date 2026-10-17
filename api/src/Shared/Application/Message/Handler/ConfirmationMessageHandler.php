<?php

declare(strict_types=1);

namespace App\Shared\Application\Message\Handler;

use App\Shared\Application\Message\ConfirmationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler pour ConfirmationMessage.
 * Traite les messages de confirmation sur le transport async_transactional.
 */
#[AsMessageHandler]
final class ConfirmationMessageHandler
{
    public function __invoke(ConfirmationMessage $message): void
    {
        // En production : envoyer un e-mail de confirmation
        // Pour le test : simplement ne rien faire (ou logger)
    }
}
