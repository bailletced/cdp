<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\LoginLink\Exception\ExpiredLoginLinkException;

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
     * Détermine le type d'erreur en parcourant toute la chaîne d'exceptions.
     *
     * Chaîne typique Symfony LoginLink :
     *   InvalidLoginLinkAuthenticationException (authenticator)
     *     └─ ExpiredLoginLinkException | InvalidLoginLinkException (handler)
     *          └─ ExpiredSignatureException | InvalidSignatureException | UserNotFoundException
     */
    private function determineErrorType(AuthenticationException $exception): string
    {
        $current = $exception;

        while ($current !== null) {
            if ($current instanceof ExpiredLoginLinkException || $current instanceof ExpiredSignatureException) {
                return 'expired';
            }

            if ($current instanceof InvalidSignatureException) {
                return 'altered';
            }

            if ($current instanceof UserNotFoundException) {
                return 'revoked';
            }

            $current = $current->getPrevious();
        }

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
