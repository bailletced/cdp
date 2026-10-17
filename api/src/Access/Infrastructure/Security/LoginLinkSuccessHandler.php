<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Success handler pour login_link.
 * Renvoie la cible au format JSON pour la SPA.
 * Critère F02-T08 : POST /auth/connexion ouvre la session et renvoie la cible.
 */
final class LoginLinkSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * Called when authentication succeeded.
     * Renvoie un JSON avec l'URL de redirection (cible).
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        // Récupérer la cible depuis la requête
        // Pour POST, _target_path est dans les paramètres de la requête
        // Pour GET, _target_path serait dans les paramètres de query
        $targetPath = $request->request->get('_target_path') ?? $request->query->get('_target_path') ?? '/';
        
        // Renvoie un JSON avec la cible pour que la SPA puisse rediriger
        return new JsonResponse([
            'target' => $targetPath,
        ]);
    }
}
