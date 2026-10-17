<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * Implémentation de PSR-20 ClockInterface pour les tests, permettant de figer l'heure.
 */
final class MockClock implements ClockInterface
{
    private DateTimeImmutable $dateTime;

    public function __construct(DateTimeImmutable|int $dateTimeOrTimestamp = new DateTimeImmutable('now', new DateTimeZone('UTC')))
    {
        if ($dateTimeOrTimestamp instanceof DateTimeImmutable) {
            $this->dateTime = $dateTimeOrTimestamp;
        } else {
            $this->dateTime = new DateTimeImmutable('@' . $dateTimeOrTimestamp, new DateTimeZone('UTC'));
        }
    }

    #[\Override]
    public function now(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * Retourne l'heure actuelle comme un timestamp Unix.
     * Méthode additionnelle pour la compatibilité.
     */
    public function currentTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    /**
     * Met à jour le timestamp de l'horloge.
     */
    public function setTimestamp(int $timestamp): void
    {
        $this->dateTime = new DateTimeImmutable('@' . $timestamp, new DateTimeZone('UTC'));
    }

    /**
     * Met à jour l'heure de l'horloge.
     */
    public function setDateTime(DateTimeImmutable $dateTime): void
    {
        $this->dateTime = $dateTime;
    }
}
