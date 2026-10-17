<?php

declare(strict_types=1);

namespace App\Shared\Application\Message\Handler;

use App\Shared\Application\Message\ReminderMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler pour ReminderMessage.
 * Traite les messages de rappel sur le transport async_transactional.
 */
#[AsMessageHandler]
final class ReminderMessageHandler
{
    public function __invoke(ReminderMessage $message): void
    {
        // En production : envoyer un e-mail de rappel
        // Pour le test : simplement ne rien faire (ou logger)
    }
}
