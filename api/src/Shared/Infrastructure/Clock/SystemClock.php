<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * Implémentation de PSR-20 ClockInterface utilisant l'heure système.
 */
final class SystemClock implements ClockInterface
{
    #[\Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * Retourne l'heure actuelle comme un timestamp Unix.
     * Méthode additionnelle pour la compatibilité avec l'ancien code.
     */
    public function currentTimestamp(): int
    {
        return time();
    }
}
