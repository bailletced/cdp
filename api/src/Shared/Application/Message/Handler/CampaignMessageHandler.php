<?php

declare(strict_types=1);

namespace App\Shared\Application\Message\Handler;

use App\Shared\Application\Message\CampaignMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler pour CampaignMessage.
 * Traite les campagnes en masse sur le transport async_bulk.
 */
#[AsMessageHandler]
final class CampaignMessageHandler
{
    public function __invoke(CampaignMessage $message): void
    {
        // En production : envoyer la campagne à tous les destinataires
        // Pour le test : simplement ne rien faire (ou logger)
    }
}
