<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Failure handler pour login_link.
 * Renvoie une erreur 401 typée pour les liens expirés/altérés/révoqués.
 * Critère F02-T08 : lien expiré, altéré ou révoqué → 401 typé.
 */
final class LoginLinkFailureHandler implements AuthenticationFailureHandlerInterface
{
    /**
     * Called when authentication failed.
     * Renvoie un JSON 401 avec le type d'erreur.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $errorType = $this->determineErrorType($exception);
        
        return new JsonResponse([
            'error' => 'invalid_login_link',
            'type' => $errorType,
            'message' => $this->getErrorMessage($errorType),
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Détermine le type d'erreur à partir de l'exception.
     */
    private function determineErrorType(AuthenticationException $exception): string
    {
        $message = $exception->getMessage();
        
        // Les messages d'erreur de Symfony Security login_link
        if (str_contains($message, 'expired') || str_contains($message, 'Expired')) {
            return 'expired';
        }
        
        if (str_contains($message, 'invalid') || str_contains($message, 'Invalid')) {
            return 'altered';
        }
        
        if (str_contains($message, 'user not found') || str_contains($message, 'User not found')) {
            return 'revoked';
        }
        
        // Par défaut, on considère que c'est un lien invalide
        return 'invalid';
    }

    /**
     * Retourne un message d'erreur convivial.
     */
    private function getErrorMessage(string $errorType): string
    {
        return match ($errorType) {
            'expired' => 'Ce lien de connexion a expiré. Veuillez en demander un nouveau.',
            'altered' => 'Ce lien de connexion est invalide (altéré).',
            'revoked' => 'Ce lien de connexion a été révoqué.',
            default => 'Lien de connexion invalide.',
        };
    }
}
