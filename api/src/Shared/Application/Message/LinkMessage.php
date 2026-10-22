<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * Message pour l'envoi d'un e-mail avec un lien.
 * Utilise le transport async_transactional pour une exécution prioritaire.
 */
final readonly class LinkMessage
{
    public function __construct(
        public Email $email,
        public string $link,
        public string $subject,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
    }
}
