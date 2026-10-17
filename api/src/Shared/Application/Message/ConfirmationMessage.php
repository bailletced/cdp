<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * Message pour l'envoi d'un e-mail de confirmation.
 * Utilise le transport async_transactional pour une exécution prioritaire.
 */
final readonly class ConfirmationMessage implements MessageInterface
{
    public function __construct(
        public Email $email,
        public string $token,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
    }
}
