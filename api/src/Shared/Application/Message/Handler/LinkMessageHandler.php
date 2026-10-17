<?php

declare(strict_types=1);

namespace App\Shared\Application\Message\Handler;

use App\Shared\Application\Message\LinkMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler pour LinkMessage.
 * Traite les messages avec lien sur le transport async_transactional.
 */
#[AsMessageHandler]
final class LinkMessageHandler
{
    public function __invoke(LinkMessage $message): void
    {
        // En production : envoyer un e-mail avec le lien
        // Pour le test : simplement ne rien faire (ou logger)
    }
}
