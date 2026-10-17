<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

use DateTimeImmutable;

/**
 * Message pour l'envoi d'une campagne (bulk).
 * Utilise le transport async_bulk pour un traitement en masse.
 */
final readonly class CampaignMessage implements MessageInterface
{
    /**
     * @param array<array-key, string> $recipients
     */
    public function __construct(
        public string $campaignId,
        public array $recipients,
        public string $subject,
        public string $template,
        public DateTimeImmutable $scheduledAt,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
    }
}
