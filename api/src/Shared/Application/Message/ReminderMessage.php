<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * Message pour l'envoi d'un e-mail de rappel.
 * Utilise le transport async_transactional pour une exécution prioritaire.
 */
final readonly class ReminderMessage
{
    public function __construct(
        public Email $email,
        public string $content,
        public DateTimeImmutable $scheduledAt,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
    }
}
