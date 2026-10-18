<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Endpoint de statut d'authentification (F02-T09).
 * GET /auth/status renvoie si l'utilisateur courant est authentifié.
 * Utilisé par les tests d'acceptation pour vérifier le remember-me.
 */
final class AuthStatusController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function status(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $authenticated = $token !== null && $token->getUserIdentifier() !== '';

        return new JsonResponse([
            'authenticated' => $authenticated,
            'user' => $authenticated ? $token->getUserIdentifier() : null,
        ]);
    }
}
