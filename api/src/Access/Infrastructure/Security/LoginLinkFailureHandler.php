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
 * Failure handler for login_link.
 * Returns a typed 401 error for expired/tampered/revoked links.
 * F02-T08: expired, tampered or revoked link → typed 401.
 */
final class LoginLinkFailureHandler implements AuthenticationFailureHandlerInterface
{
    /**
     * Called when authentication failed.
     * Returns a 401 JSON with the error type.
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
     * Determines the error type by walking the entire exception chain.
     *
     * Typical Symfony LoginLink chain:
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
     * Returns a user-friendly error message.
     * TODO: move to translation files (translations/) instead of hardcoding French strings.
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
