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
     * 
     * Arbre des exceptions Symfony LoginLink :
     * InvalidLoginLinkAuthenticationException (authenticator)
     *   └─ InvalidLoginLinkException (handler)
     *       ├─ ExpiredLoginLinkException (avec cause: ExpiredSignatureException)
     *       ├─ InvalidSignatureException (cause directe)
     *       └─ UserNotFoundException (cause directe)
     */
    private function determineErrorType(AuthenticationException $exception): string
    {
        // Niveau 1 : InvalidLoginLinkAuthenticationException
        $previous = $exception->getPrevious();
        
        if ($previous !== null) {
            $previousClass = get_class($previous);
            
            // ExpiredLoginLinkException est déjà typée
            if (str_contains($previousClass, 'ExpiredLoginLinkException')) {
                return 'expired';
            }
            
            // InvalidLoginLinkException peut avoir différentes causes
            if (str_contains($previousClass, 'InvalidLoginLinkException')) {
                $cause = $previous->getPrevious();
                if ($cause !== null) {
                    $causeClass = get_class($cause);
                    
                    if (str_contains($causeClass, 'InvalidSignatureException')) {
                        return 'altered';
                    }
                    
                    if (str_contains($causeClass, 'UserNotFoundException')) {
                        return 'revoked';
                    }
                }
                // Si pas de cause identifiable, c'est un lien invalide
                return 'invalid';
            }
            
            // Vérification directe (pour ExpiredSignatureException, InvalidSignatureException, UserNotFoundException)
            if (str_contains($previousClass, 'ExpiredSignatureException')) {
                return 'expired';
            }
            
            if (str_contains($previousClass, 'InvalidSignatureException')) {
                return 'altered';
            }
            
            if (str_contains($previousClass, 'UserNotFoundException')) {
                return 'revoked';
            }
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
