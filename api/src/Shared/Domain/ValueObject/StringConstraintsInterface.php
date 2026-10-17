<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Interface pour les Value Objects qui ont des contraintes de longueur de chaîne.
 * Permet à AssertValueObject de récupérer les bornes min/max sans utiliser la réflexion.
 */
interface StringConstraintsInterface
{
    /**
     * Retourne la longueur minimale autorisée, ou null si non applicable.
     */
    public static function getMinLength(): ?int;

    /**
     * Retourne la longueur maximale autorisée, ou null si non applicable.
     */
    public static function getMaxLength(): ?int;
}
